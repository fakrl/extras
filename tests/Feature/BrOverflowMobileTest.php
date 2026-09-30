<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/** BR.3.1: penjaga overflow horizontal di HP (360/390px), dicek di sumbernya, bukan overflow-x: hidden. */
class BrOverflowMobileTest extends TestCase
{
    public function test_tabel_non_pdf_dibungkus_table_container(): void
    {
        $lolos = [];
        foreach (File::allFiles(resource_path('views')) as $f) {
            $path = str_replace('\\', '/', $f->getRelativePathname());
            if (str_contains($path, 'pdf') || str_starts_with($path, 'emails/')) {
                continue;
            }
            $s = $f->getContents();
            preg_match_all('/<table\b/', $s, $m, PREG_OFFSET_CAPTURE);
            foreach ($m[0] as [, $pos]) {
                $sebelum = substr($s, 0, $pos);
                if (strrpos($sebelum, 'table-container') === false || strrpos($sebelum, 'table-container') < (int) strrpos($sebelum, '</table>')) {
                    $lolos[] = $path.':'.(substr_count($sebelum, "\n") + 1);
                }
            }
        }

        $this->assertSame([], $lolos, 'Tabel tanpa .table-container');
    }

    public function test_kelas_global_pencegah_overflow_ada(): void
    {
        $css = File::get(resource_path('views/layouts/app.blade.php'));

        $this->assertStringContainsString('.table-container { overflow-x: auto; position: relative; }', $css);
        $this->assertStringContainsString('.main-area { flex: 1; display: flex; flex-direction: column; min-width: 0; }', $css);
        $this->assertStringContainsString('.dash-tiga > .card { margin: 0; min-width: 0; }', $css);
        $this->assertMatchesRegularExpression('/img, video \{[^}]*max-width: 100%/', File::get(resource_path('views/partials/theme-style.blade.php')));
        $this->assertDoesNotMatchRegularExpression('/(^|[\s,}])(html|body)\s*\{[^}]*overflow-x:\s*hidden/', $css.File::get(resource_path('views/partials/theme-style.blade.php')));
    }
}
