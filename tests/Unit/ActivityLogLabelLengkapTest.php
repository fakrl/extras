<?php

namespace Tests\Unit;

use App\Models\ActivityLog;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

class ActivityLogLabelLengkapTest extends TestCase
{
    /**
     * Scan app/ untuk semua action key yang dipakai ActivityLog::record(action, ...),
     * pastikan semuanya punya label eksplisit di ACTION_LABELS (bukan cuma fallback
     * ucfirst(strtolower(...)) otomatis). Action key selalu string literal UPPER_SNAKE_CASE
     * dalam ~120 karakter pertama setelah "ActivityLog::record(" (literal langsung atau
     * ternary 2 kemungkinan), jadi aman diambil lewat window-scan tanpa daftar manual.
     */
    public function test_semua_action_key_activitylog_record_punya_label_eksplisit(): void
    {
        $keys = [];
        $finder = (new Finder)->files()->in(dirname(__DIR__, 2).'/app')->name('*.php');

        foreach ($finder as $file) {
            $code = $file->getContents();
            $pos = 0;
            while (($pos = strpos($code, 'ActivityLog::record(', $pos)) !== false) {
                $window = substr($code, $pos, 150);
                preg_match_all("/'([A-Z][A-Z0-9_]*)'/", $window, $matches);
                foreach ($matches[1] as $key) {
                    $keys[$key] = $file->getRelativePathname();
                }
                $pos += 20;
            }
        }

        $this->assertNotEmpty($keys, 'Scan tidak menemukan satupun action key - kemungkinan pola regex/window salah, cek manual dulu.');
        $this->assertGreaterThanOrEqual(50, count($keys), 'Jumlah action key ketemu jauh lebih sedikit dari ekspektasi - cek window-scan.');

        foreach ($keys as $key => $file) {
            $this->assertArrayHasKey(
                $key,
                ActivityLog::ACTION_LABELS,
                "Action key '{$key}' (dipakai di {$file}) belum punya label eksplisit di ActivityLog::ACTION_LABELS."
            );
        }
    }
}
