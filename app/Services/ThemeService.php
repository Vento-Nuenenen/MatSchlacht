<?php

namespace App\Services;

/**
 * Derives the colour palette of the app from the primary colour configured under
 * Einstellungen › Erscheinungsbild and renders it as CSS custom properties, so that the
 * colour can be changed without rebuilding the assets.
 */
class ThemeService
{
    public const DEFAULT_PRIMARY_COLOR = '#b1138b';

    public function __construct(protected ComponentManager $components) {}

    public function primaryColor(): string
    {
        $color = strtolower((string) $this->components->setting('appearance', 'primary_color'));

        return preg_match('/^#[0-9a-f]{6}$/', $color) === 1 ? $color : self::DEFAULT_PRIMARY_COLOR;
    }

    /**
     * The CSS custom properties for the light and the dark theme.
     */
    public function cssVariables(): string
    {
        $primary = $this->primaryColor();
        $rgb = $this->rgb($primary);
        $contrast = $this->luminance($rgb) > 0.45 ? '#1f1a24' : '#ffffff';

        $light = [
            '--app-primary' => $primary,
            '--app-primary-rgb' => implode(', ', $rgb),
            '--app-primary-hover' => $this->hex($this->mix($rgb, [0, 0, 0], 0.12)),
            '--app-primary-active' => $this->hex($this->mix($rgb, [0, 0, 0], 0.22)),
            '--app-primary-contrast' => $contrast,
            '--app-primary-soft' => $this->hex($this->mix($rgb, [255, 255, 255], 0.9)),
            '--app-primary-soft-strong' => $this->hex($this->mix($rgb, [255, 255, 255], 0.8)),
            '--bs-primary' => $primary,
            '--bs-primary-rgb' => implode(', ', $rgb),
            '--bs-primary-text-emphasis' => $this->hex($this->mix($rgb, [0, 0, 0], 0.4)),
            '--bs-primary-bg-subtle' => $this->hex($this->mix($rgb, [255, 255, 255], 0.88)),
            '--bs-primary-border-subtle' => $this->hex($this->mix($rgb, [255, 255, 255], 0.65)),
            '--bs-link-color' => $primary,
            '--bs-link-color-rgb' => implode(', ', $rgb),
            '--bs-link-hover-color' => $this->hex($this->mix($rgb, [0, 0, 0], 0.2)),
            '--bs-link-hover-color-rgb' => implode(', ', $this->mix($rgb, [0, 0, 0], 0.2)),
        ];

        // On dark backgrounds a lighter tint of the colour keeps text and links readable.
        $tint = $this->mix($rgb, [255, 255, 255], 0.35);
        $dark = [
            '--app-primary-soft' => $this->hex($this->mix($rgb, [24, 20, 28], 0.78)),
            '--app-primary-soft-strong' => $this->hex($this->mix($rgb, [24, 20, 28], 0.62)),
            '--bs-primary-text-emphasis' => $this->hex($this->mix($rgb, [255, 255, 255], 0.5)),
            '--bs-primary-bg-subtle' => $this->hex($this->mix($rgb, [24, 20, 28], 0.75)),
            '--bs-primary-border-subtle' => $this->hex($this->mix($rgb, [24, 20, 28], 0.45)),
            '--bs-link-color' => $this->hex($tint),
            '--bs-link-color-rgb' => implode(', ', $tint),
            '--bs-link-hover-color' => $this->hex($this->mix($rgb, [255, 255, 255], 0.55)),
            '--bs-link-hover-color-rgb' => implode(', ', $this->mix($rgb, [255, 255, 255], 0.55)),
            '--app-primary-text' => $this->hex($tint),
        ];
        $light['--app-primary-text'] = $primary;

        return ':root,[data-bs-theme=light]{'.$this->declarations($light).'}[data-bs-theme=dark]{'.$this->declarations($dark).'}';
    }

    /**
     * @param  array<string, string>  $properties
     */
    protected function declarations(array $properties): string
    {
        return collect($properties)->map(fn (string $value, string $property) => "{$property}:{$value}")->implode(';');
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    protected function rgb(string $hex): array
    {
        return [(int) hexdec(substr($hex, 1, 2)), (int) hexdec(substr($hex, 3, 2)), (int) hexdec(substr($hex, 5, 2))];
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    protected function hex(array $rgb): string
    {
        return sprintf('#%02x%02x%02x', ...$rgb);
    }

    /**
     * Mix a colour with another one, $weight being the share of the other colour.
     *
     * @param  array{0: int, 1: int, 2: int}  $rgb
     * @param  array{0: int, 1: int, 2: int}  $with
     * @return array{0: int, 1: int, 2: int}
     */
    protected function mix(array $rgb, array $with, float $weight): array
    {
        return [
            (int) round($rgb[0] * (1 - $weight) + $with[0] * $weight),
            (int) round($rgb[1] * (1 - $weight) + $with[1] * $weight),
            (int) round($rgb[2] * (1 - $weight) + $with[2] * $weight),
        ];
    }

    /**
     * The relative luminance (WCAG) of a colour.
     *
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    protected function luminance(array $rgb): float
    {
        $channels = array_map(function (int $channel): float {
            $value = $channel / 255;

            return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, $rgb);

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }
}
