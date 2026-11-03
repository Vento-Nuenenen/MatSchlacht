<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;

/**
 * Resolves whether app components are enabled and how they are configured.
 * Defaults come from config/components.php, overrides are stored in the "settings" table.
 */
class ComponentManager
{
    public const CACHE_KEY = 'components.settings';

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $storedValues = null;

    /**
     * @return array<string, array{label: string, description: string, enabled: bool, toggleable?: bool, settings: array<string, array{type: string, label: string, default: mixed, options?: array<string, string>, group?: string, multiline?: bool, hidden?: bool, rules?: array<int, mixed>}>}>
     */
    public function definitions(): array
    {
        /** @var array<string, array{label: string, description: string, enabled: bool, toggleable?: bool, settings: array<string, array{type: string, label: string, default: mixed, options?: array<string, string>, group?: string, multiline?: bool, hidden?: bool, rules?: array<int, mixed>}>}> $definitions */
        $definitions = config('components', []);

        return $definitions;
    }

    public function isEnabled(string $component): bool
    {
        $definition = $this->definition($component);

        if (! $this->isToggleable($component)) {
            return true;
        }

        return (bool) $this->storedValue("{$component}.enabled", $definition['enabled']);
    }

    public function setting(string $component, string $key): mixed
    {
        $definition = $this->definition($component);

        if (! array_key_exists($key, $definition['settings'])) {
            throw new InvalidArgumentException("Unknown setting [{$key}] for component [{$component}].");
        }

        $settingDefinition = $definition['settings'][$key];
        $storageKey = "{$component}.{$key}";

        if ($settingDefinition['type'] === 'secret' && $this->hasStoredValue($storageKey)) {
            return $this->decrypt($this->storedValue($storageKey, null));
        }

        return $this->storedValue($storageKey, $settingDefinition['default']);
    }

    /**
     * Whether a secret has been saved (its value is never shown in the settings form).
     */
    public function hasStoredSecret(string $component, string $key): bool
    {
        return $this->hasStoredValue("{$component}.{$key}") && $this->storedValue("{$component}.{$key}", null) !== null;
    }

    /**
     * Components that cannot be switched off, e.g. the file storage, are always enabled.
     */
    public function isToggleable(string $component): bool
    {
        return $this->definition($component)['toggleable'] ?? true;
    }

    /**
     * Split a comma separated setting (e.g. a list of e-mail addresses) into its trimmed values.
     *
     * @return array<int, string>
     */
    public function settingList(string $component, string $key): array
    {
        $value = $this->setting($component, $key);

        if (! is_string($value)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value)), fn (string $entry) => $entry !== ''));
    }

    /**
     * Persist the given component states and settings.
     *
     * @param  array<string, array{enabled?: mixed, settings?: array<string, mixed>}>  $values
     */
    public function update(array $values): void
    {
        foreach ($this->definitions() as $component => $definition) {
            if (! array_key_exists($component, $values)) {
                continue;
            }

            if ($definition['toggleable'] ?? true) {
                Setting::updateOrCreate(
                    ['key' => "{$component}.enabled"],
                    ['value' => (bool) ($values[$component]['enabled'] ?? false)]
                );
            }

            foreach ($definition['settings'] as $key => $settingDefinition) {
                $value = $values[$component]['settings'][$key] ?? null;

                // An empty secret field keeps the saved secret, the form never shows it.
                if ($settingDefinition['type'] === 'secret' && ($value === null || $value === '')) {
                    continue;
                }

                Setting::updateOrCreate(
                    ['key' => "{$component}.{$key}"],
                    ['value' => $this->normalize($settingDefinition['type'], $value)]
                );
            }
        }

        $this->flush();
    }

    /**
     * Save a single setting without touching the other settings of the component, e.g. an OAuth token.
     */
    public function updateSetting(string $component, string $key, mixed $value): void
    {
        $definition = $this->definition($component);

        if (! array_key_exists($key, $definition['settings'])) {
            throw new InvalidArgumentException("Unknown setting [{$key}] for component [{$component}].");
        }

        Setting::updateOrCreate(
            ['key' => "{$component}.{$key}"],
            ['value' => $this->normalize($definition['settings'][$key]['type'], $value)]
        );

        $this->flush();
    }

    public function flush(): void
    {
        $this->storedValues = null;
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array{label: string, description: string, enabled: bool, toggleable?: bool, settings: array<string, array{type: string, label: string, default: mixed, options?: array<string, string>, group?: string, multiline?: bool, hidden?: bool, rules?: array<int, mixed>}>}
     */
    protected function definition(string $component): array
    {
        $definitions = $this->definitions();

        if (! array_key_exists($component, $definitions)) {
            throw new InvalidArgumentException("Unknown app component [{$component}].");
        }

        return $definitions[$component];
    }

    protected function hasStoredValue(string $key): bool
    {
        $values = $this->storedValues ??= $this->loadStoredValues();

        return array_key_exists($key, $values);
    }

    protected function decrypt(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return null;
        }
    }

    protected function storedValue(string $key, mixed $default): mixed
    {
        $values = $this->storedValues ??= $this->loadStoredValues();

        return array_key_exists($key, $values) ? $values[$key] : $default;
    }

    /**
     * Load all stored values. Falls back to the defaults while the settings table does not exist yet.
     *
     * @return array<string, mixed>
     */
    protected function loadStoredValues(): array
    {
        try {
            /** @var array<string, mixed> $values */
            $values = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::pluck('value', 'key')->all());

            return $values;
        } catch (QueryException) {
            return [];
        }
    }

    protected function normalize(string $type, mixed $value): mixed
    {
        return match ($type) {
            'bool' => (bool) $value,
            'integer' => is_numeric($value) ? (int) $value : null,
            'secret' => is_string($value) && $value !== '' ? Crypt::encryptString($value) : null,
            'color' => is_string($value) ? strtolower(trim($value)) : null,
            'email_list' => is_string($value)
                ? implode(', ', array_filter(array_map('trim', explode(',', $value)), fn (string $entry) => $entry !== '')) ?: null
                : null,
            default => is_string($value) && trim($value) !== '' ? trim($value) : null,
        };
    }
}
