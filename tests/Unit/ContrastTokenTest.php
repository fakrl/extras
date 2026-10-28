<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * CT.1.4 — Test kontras WCAG 2.x untuk token M3 JBTB.
 *
 * Memastikan semua pasangan fg/bg memenuhi rasio kontras ≥ 4.5:1
 * di tema terang (light) maupun gelap (dark).
 *
 * Rumus: linearize(c) = c/12.92 jika c<=0.04045, else ((c+0.055)/1.055)^2.4
 * L = 0.2126*R + 0.7152*G + 0.0722*B
 * ratio = (L_lighter + 0.05) / (L_darker + 0.05)
 */
class ContrastTokenTest extends TestCase
{
    // ── Nilai M3 light ───────────────────────────────────────────────────────
    private const LIGHT = [
        '--md-primary' => '#15803D',
        '--md-on-primary' => '#FFFFFF',
        '--md-primary-container' => '#B7F1CB',
        '--md-on-primary-container' => '#002111',
        '--md-secondary' => '#506353',
        '--md-on-secondary' => '#FFFFFF',
        '--md-secondary-container' => '#D3E8D2',
        '--md-on-secondary-container' => '#0E1F13',
        '--md-tertiary' => '#3A6659',
        '--md-on-tertiary' => '#FFFFFF',
        '--md-tertiary-container' => '#BCECDD',
        '--md-on-tertiary-container' => '#002019',
        '--md-error' => '#BA1A1A',
        '--md-on-error' => '#FFFFFF',
        '--md-error-container' => '#FFDAD6',
        '--md-on-error-container' => '#410002',
        '--md-surface' => '#F6FBF3',
        '--md-surface-container-low' => '#F0F5EE',
        '--md-surface-container' => '#EAF0E8',
        '--md-on-surface' => '#171D17',
        '--md-on-surface-variant' => '#404942',
        '--md-custom-warning' => '#D97706',
        '--md-custom-warning-container' => '#FEF3C7',
        '--md-custom-on-warning-container' => '#78350F',
        '--md-custom-urgent' => '#C2410C',
        '--md-custom-urgent-container' => '#FFEDD5',
        '--md-custom-on-urgent-container' => '#7C2D12',
        '--md-custom-info' => '#1D4ED8',
        '--md-custom-info-container' => '#DBEAFE',
        '--md-custom-on-info-container' => '#1E3A8A',
    ];

    // ── Nilai M3 dark ────────────────────────────────────────────────────────
    private const DARK = [
        '--md-primary' => '#6EDB96',
        '--md-on-primary' => '#003920',
        '--md-primary-container' => '#005230',
        '--md-on-primary-container' => '#8CF6B1',
        '--md-secondary' => '#B7CCBA',
        '--md-on-secondary' => '#223526',
        '--md-secondary-container' => '#384B3C',
        '--md-on-secondary-container' => '#D3E8D2',
        '--md-tertiary' => '#A0D0C1',
        '--md-on-tertiary' => '#00382C',
        '--md-tertiary-container' => '#1F4E41',
        '--md-on-tertiary-container' => '#BCECDD',
        '--md-error' => '#FFB4AB',
        '--md-on-error' => '#690005',
        '--md-error-container' => '#93000A',
        '--md-on-error-container' => '#FFDAD6',
        '--md-surface' => '#101510',
        '--md-surface-container-low' => '#171D17',
        '--md-surface-container' => '#1C221C',
        '--md-on-surface' => '#DEE4DC',
        '--md-on-surface-variant' => '#BFC9C0',
        '--md-custom-warning' => '#F59E0B',
        '--md-custom-warning-container' => '#78350F',
        '--md-custom-on-warning-container' => '#FEF3C7',
        '--md-custom-urgent' => '#FB923C',
        '--md-custom-urgent-container' => '#7C2D12',
        '--md-custom-on-urgent-container' => '#FFEDD5',
        '--md-custom-info' => '#60A5FA',
        '--md-custom-info-container' => '#1E3A8A',
        '--md-custom-on-info-container' => '#DBEAFE',
    ];

    // ── Helper: parse hex → [r,g,b] 0-1 ────────────────────────────────────

    private function hexToLinear(string $hex): array
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $lin = fn (float $c): float => $c <= 0.04045
            ? $c / 12.92
            : (($c + 0.055) / 1.055) ** 2.4;

        return [$lin($r), $lin($g), $lin($b)];
    }

    private function luminance(string $hex): float
    {
        [$r, $g, $b] = $this->hexToLinear($hex);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    private function contrast(string $fg, string $bg): float
    {
        $l1 = $this->luminance($fg);
        $l2 = $this->luminance($bg);
        if ($l1 < $l2) {
            [$l1, $l2] = [$l2, $l1];
        }

        return ($l1 + 0.05) / ($l2 + 0.05);
    }

    private function assertContrast(string $fg, string $bg, string $label, float $min = 4.5): void
    {
        $ratio = $this->contrast($fg, $bg);
        $this->assertGreaterThanOrEqual(
            $min,
            $ratio,
            sprintf('%s: kontras %.2f:1 < %.1f:1 (fg=%s bg=%s)', $label, $ratio, $min, $fg, $bg)
        );
    }

    // ── Tests: light theme ───────────────────────────────────────────────────

    public function test_light_on_primary_on_primary_bg(): void
    {
        $t = self::LIGHT;
        $this->assertContrast($t['--md-on-primary'], $t['--md-primary'], 'light on-primary/primary');
    }

    public function test_light_on_primary_container(): void
    {
        $t = self::LIGHT;
        $this->assertContrast($t['--md-on-primary-container'], $t['--md-primary-container'], 'light on-primary-container/primary-container');
    }

    public function test_light_on_secondary_container(): void
    {
        $t = self::LIGHT;
        $this->assertContrast($t['--md-on-secondary-container'], $t['--md-secondary-container'], 'light on-secondary-container/secondary-container');
    }

    public function test_light_on_tertiary_container(): void
    {
        $t = self::LIGHT;
        $this->assertContrast($t['--md-on-tertiary-container'], $t['--md-tertiary-container'], 'light on-tertiary-container/tertiary-container');
    }

    public function test_light_on_error_container(): void
    {
        $t = self::LIGHT;
        $this->assertContrast($t['--md-on-error-container'], $t['--md-error-container'], 'light on-error-container/error-container');
    }

    public function test_light_on_surface_on_surface_bg(): void
    {
        $t = self::LIGHT;
        $this->assertContrast($t['--md-on-surface'], $t['--md-surface'], 'light on-surface/surface');
    }

    public function test_light_on_surface_variant_on_surface(): void
    {
        $t = self::LIGHT;
        $this->assertContrast($t['--md-on-surface-variant'], $t['--md-surface'], 'light on-surface-variant/surface');
    }

    public function test_light_on_warning_container(): void
    {
        $t = self::LIGHT;
        $this->assertContrast($t['--md-custom-on-warning-container'], $t['--md-custom-warning-container'], 'light on-warning-container/warning-container');
    }

    public function test_light_on_urgent_container(): void
    {
        $t = self::LIGHT;
        $this->assertContrast($t['--md-custom-on-urgent-container'], $t['--md-custom-urgent-container'], 'light on-urgent-container/urgent-container');
    }

    public function test_light_on_info_container(): void
    {
        $t = self::LIGHT;
        $this->assertContrast($t['--md-custom-on-info-container'], $t['--md-custom-info-container'], 'light on-info-container/info-container');
    }

    // ── Tests: dark theme ────────────────────────────────────────────────────

    public function test_dark_on_primary_on_primary_bg(): void
    {
        $t = self::DARK;
        $this->assertContrast($t['--md-on-primary'], $t['--md-primary'], 'dark on-primary/primary');
    }

    public function test_dark_on_primary_container(): void
    {
        $t = self::DARK;
        $this->assertContrast($t['--md-on-primary-container'], $t['--md-primary-container'], 'dark on-primary-container/primary-container');
    }

    public function test_dark_on_secondary_container(): void
    {
        $t = self::DARK;
        $this->assertContrast($t['--md-on-secondary-container'], $t['--md-secondary-container'], 'dark on-secondary-container/secondary-container');
    }

    public function test_dark_on_tertiary_container(): void
    {
        $t = self::DARK;
        $this->assertContrast($t['--md-on-tertiary-container'], $t['--md-tertiary-container'], 'dark on-tertiary-container/tertiary-container');
    }

    public function test_dark_on_error_container(): void
    {
        $t = self::DARK;
        $this->assertContrast($t['--md-on-error-container'], $t['--md-error-container'], 'dark on-error-container/error-container');
    }

    public function test_dark_on_surface_on_surface_bg(): void
    {
        $t = self::DARK;
        $this->assertContrast($t['--md-on-surface'], $t['--md-surface'], 'dark on-surface/surface');
    }

    public function test_dark_on_surface_variant_on_surface(): void
    {
        $t = self::DARK;
        $this->assertContrast($t['--md-on-surface-variant'], $t['--md-surface'], 'dark on-surface-variant/surface');
    }

    public function test_dark_on_warning_container(): void
    {
        $t = self::DARK;
        $this->assertContrast($t['--md-custom-on-warning-container'], $t['--md-custom-warning-container'], 'dark on-warning-container/warning-container');
    }

    public function test_dark_on_urgent_container(): void
    {
        $t = self::DARK;
        $this->assertContrast($t['--md-custom-on-urgent-container'], $t['--md-custom-urgent-container'], 'dark on-urgent-container/urgent-container');
    }

    public function test_dark_on_info_container(): void
    {
        $t = self::DARK;
        $this->assertContrast($t['--md-custom-on-info-container'], $t['--md-custom-info-container'], 'dark on-info-container/info-container');
    }
}
