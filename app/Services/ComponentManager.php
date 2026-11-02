<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
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
     * @return array<string, array{label: string, description: string, enabled: bool, settings: array<string, array{type: string, label: string, default: mixed}>}>
     */
    public function definitions(): array
    {
        /** @var array<string, array{label: string, description: string, enabled: bool, settings: array<string, array{type: string, label: string, default: mixed}>}> $definitions */
        $definitions = config('components', []);

        return $definitions;
    }

    public function isEnabled(string $component): bool
    {
        $definition = $this->definition($component);

        return (bool) $this->storedValue("{$component}.enabled", $definition['enabled']);
    }

    public function setting(string $component, string $key): mixed
    {
        $definition = $this->definition($component);

        if (! array_key_exists($key, $definition['settings'])) {
            throw new InvalidArgumentException("Unknown setting [{$key}] for component [{$component}].");
        }

        return $this->storedValue("{$component}.{$key}", $definition['settings'][$key]['default']);
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

            Setting::updateOrCreate(
                ['key' => "{$component}.enabled"],
                ['value' => (bool) ($values[$component]['enabled'] ?? false)]
            );

            foreach ($definition['settings'] as $key => $settingDefinition) {
                $value = $values[$component]['settings'][$key] ?? null;

                Setting::updateOrCreate(
                    ['key' => "{$component}.{$key}"],
                    ['value' => $this->normalize($settingDefinition['type'], $value)]
                );
            }
        }

        $this->flush();
    }

    public function flush(): void
    {
        $this->storedValues = null;
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array{label: string, description: string, enabled: bool, settings: array<string, array{type: string, label: string, default: mixed}>}
     */
    protected function definition(string $component): array
    {
        $definitions = $this->definitions();

        if (! array_key_exists($component, $definitions)) {
            throw new InvalidArgumentException("Unknown app component [{$component}].");
        }

        return $definitions[$component];
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
            'email_list' => is_string($value)
                ? implode(', ', array_filter(array_map('trim', explode(',', $value)), fn (string $entry) => $entry !== '')) ?: null
                : null,
            default => is_string($value) && trim($value) !== '' ? trim($value) : null,
        };
    }
}
