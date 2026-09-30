<?php

use App\Models\ExtrasCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// SPEC BJ (Fakrul 30 Sept): warna_kulit teks → tag grup "Warna kulit" (idempoten). Kolom lama tidak di-drop.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('extras_profiles')->whereNotNull('warna_kulit')->where('warna_kulit', '!=', '')->select('id', 'warna_kulit')
            ->eachById(function ($p) {
                $tag = ExtrasCategory::tagWarnaKulit($p->warna_kulit);
                if ($tag) {
                    DB::table('extras_category_extras_profile')->insertOrIgnore(['extras_profile_id' => $p->id, 'extras_category_id' => $tag->id]);
                }
            });
    }

    public function down(): void {}
};
