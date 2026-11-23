<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * CT.1.4 — Test kontras WCAG 2.x untuk token M3 SiCasti (teal rebrand).
 *
 * Memastikan semua pasangan fg/bg memenuhi rasio kontras ≥ 4.5:1
 * di tema terang (light) maupun gelap (dark). Pasangan non-teks
 * (misal outline vs surface) memakai ambang 3:1 sesuai WCAG 1.4.11.
 *
 * Rumus: linearize(c) = c/12.92 jika c<=0.04045, else ((c+0.055)/1.055)^2.4
 * L = 0.2126*R + 0.7152*G + 0.0722*B
 * ratio = (L_lighter + 0.05) / (L_darker + 0.05)
 */
class ContrastTokenTest extends TestCase
{
    // ── Nilai M3 light (brand-tokens.blade.php, override teal) ────────────────
    private const LIGHT = [
        '--md-primary' => '#0F6B73',
        '--md-on-primary' => '#FFFFFF',
        '--md-primary-container' => '#CDE9EA',
        '--md-on-primary-container' => '#05282B',
        '--md-secondary' => '#5A6663',
        '--md-on-secondary' => '#FFFFFF',
        '--md-secondary-container' => '#E4E9E7',
        '--md-on-secondary-container' => '#151C1B',
        '--md-tertiary' => '#6A5F4D',
        '--md-on-tertiary' => '#FFFFFF',
        '--md-tertiary-container' => '#EFE6D6',
        '--md-on-tertiary-container' => '#241B0B',
        '--md-error' => '#BA1A1A',
        '--md-on-error' => '#FFFFFF',
        '--md-error-container' => '#FFDAD6',
        '--md-on-error-container' => '#410002',
        '--md-surface' => '#F7F4EE',
        '--md-surface-container-low' => '#F2EFE8',
        '--md-surface-container' => '#EDEAE2',
        '--md-on-surface' => '#1A1714',
        '--md-on-surface-variant' => '#4A453D',
        '--md-outline' => '#7A7367',
        '--text-muted' => '#6A6459',
        '--bg-card' => '#F2EFE8',
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

    // ── Nilai M3 dark (brand-tokens.blade.php, override teal) ─────────────────
    private const DARK = [
        '--md-primary' => '#5CC3CB',
        '--md-on-primary' => '#07282B',
        '--md-primary-container' => '#0F4A50',
        '--md-on-primary-container' => '#BDEFF3',
        '--md-secondary' => '#B6C4C1',
        '--md-on-secondary' => '#1F2A28',
        '--md-secondary-container' => '#36413F',
        '--md-on-secondary-container' => '#D2DFDC',
        '--md-tertiary' => '#D8CBB4',
        '--md-on-tertiary' => '#3A2F1B',
        '--md-tertiary-container' => '#54472F',
        '--md-on-tertiary-container' => '#F1E6D0',
        '--md-error' => '#FFB4AB',
        '--md-on-error' => '#690005',
        '--md-error-container' => '#93000A',
        '--md-on-error-container' => '#FFDAD6',
        '--md-surface' => '#131110',
        '--md-surface-container-low' => '#1B1816',
        '--md-surface-container' => '#201D1A',
        '--md-on-surface' => '#F2EEE6',
        '--md-on-surface-variant' => '#CFC8BB',
        '--md-outline' => '#968F83',
        '--text-muted' => '#A39B8E',
        '--bg-card' => '#1B1816',
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

    public function test_light_primary_on_surface(): void
    {
        $t = self::LIGHT;
        $this->assertContrast($t['--md-primary'], $t['--md-surface'], 'light primary/surface');
    }

    public function test_light_primary_on_surface_container_low(): void
    {
        $t = self::LIGHT;
        $this->assertContrast($t['--md-primary'], $t['--md-surface-container-low'], 'light primary/surface-container-low');
    }

    public function test_light_text_muted_on_bg_card(): void
    {
        $t = self::LIGHT;
        $this->assertContrast($t['--text-muted'], $t['--bg-card'], 'light text-muted/bg-card');
    }

    public function test_light_primary_on_primary_container(): void
    {
        $t = self::LIGHT;
        $this->assertContrast($t['--md-primary'], $t['--md-primary-container'], 'light primary/primary-container');
    }

    public function test_light_outline_on_surface_non_text(): void
    {
        $t = self::LIGHT;
        $this->assertContrast($t['--md-outline'], $t['--md-surface'], 'light outline/surface (non-text)', 3.0);
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

    public function test_dark_primary_on_surface(): void
    {
        $t = self::DARK;
        $this->assertContrast($t['--md-primary'], $t['--md-surface'], 'dark primary/surface');
    }

    public function test_dark_primary_on_surface_container_low(): void
    {
        $t = self::DARK;
        $this->assertContrast($t['--md-primary'], $t['--md-surface-container-low'], 'dark primary/surface-container-low');
    }

    public function test_dark_text_muted_on_bg_card(): void
    {
        $t = self::DARK;
        $this->assertContrast($t['--text-muted'], $t['--bg-card'], 'dark text-muted/bg-card');
    }

    public function test_dark_primary_on_primary_container(): void
    {
        $t = self::DARK;
        $this->assertContrast($t['--md-primary'], $t['--md-primary-container'], 'dark primary/primary-container');
    }

    public function test_dark_outline_on_surface_non_text(): void
    {
        $t = self::DARK;
        $this->assertContrast($t['--md-outline'], $t['--md-surface'], 'dark outline/surface (non-text)', 3.0);
    }

    // ── Token Sorot (landing-sections-style.blade.php) ─────────────────────

    private const SOROT_LIGHT = [
        '--bg' => '#F7F4EE', '--fg' => '#1A1714', '--muted-fg' => '#6A6459', '--card' => '#FFFDF8',
        '--primary' => '#0F6B73', '--on-primary' => '#FFFFFF', '--accent-soft' => '#CDE9EA', '--on-accent-soft' => '#05282B',
    ];

    private const SOROT_DARK = [
        '--bg' => '#131110', '--fg' => '#F2EEE6', '--muted-fg' => '#A39B8E', '--card' => '#1B1816',
        '--primary' => '#5CC3CB', '--on-primary' => '#07282B', '--accent-soft' => '#0F4A50', '--on-accent-soft' => '#BDEFF3',
    ];

    public function test_sorot_light_fg_on_bg(): void
    {
        $t = self::SOROT_LIGHT;
        $this->assertContrast($t['--fg'], $t['--bg'], 'sorot light fg/bg');
    }

    public function test_sorot_light_muted_fg_on_bg(): void
    {
        $t = self::SOROT_LIGHT;
        $this->assertContrast($t['--muted-fg'], $t['--bg'], 'sorot light muted-fg/bg');
    }

    public function test_sorot_light_fg_on_card(): void
    {
        $t = self::SOROT_LIGHT;
        $this->assertContrast($t['--fg'], $t['--card'], 'sorot light fg/card');
    }

    public function test_sorot_light_on_primary_on_primary(): void
    {
        $t = self::SOROT_LIGHT;
        $this->assertContrast($t['--on-primary'], $t['--primary'], 'sorot light on-primary/primary');
    }

    public function test_sorot_light_on_accent_soft_on_accent_soft(): void
    {
        $t = self::SOROT_LIGHT;
        $this->assertContrast($t['--on-accent-soft'], $t['--accent-soft'], 'sorot light on-accent-soft/accent-soft');
    }

    public function test_sorot_dark_fg_on_bg(): void
    {
        $t = self::SOROT_DARK;
        $this->assertContrast($t['--fg'], $t['--bg'], 'sorot dark fg/bg');
    }

    public function test_sorot_dark_muted_fg_on_bg(): void
    {
        $t = self::SOROT_DARK;
        $this->assertContrast($t['--muted-fg'], $t['--bg'], 'sorot dark muted-fg/bg');
    }

    public function test_sorot_dark_fg_on_card(): void
    {
        $t = self::SOROT_DARK;
        $this->assertContrast($t['--fg'], $t['--card'], 'sorot dark fg/card');
    }

    public function test_sorot_dark_on_primary_on_primary(): void
    {
        $t = self::SOROT_DARK;
        $this->assertContrast($t['--on-primary'], $t['--primary'], 'sorot dark on-primary/primary');
    }

    public function test_sorot_dark_on_accent_soft_on_accent_soft(): void
    {
        $t = self::SOROT_DARK;
        $this->assertContrast($t['--on-accent-soft'], $t['--accent-soft'], 'sorot dark on-accent-soft/accent-soft');
    }
}
