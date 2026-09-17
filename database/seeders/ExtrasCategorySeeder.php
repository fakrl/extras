<?php

namespace Database\Seeders;

use App\Models\ExtrasCategory;
use Illuminate\Database\Seeder;

class ExtrasCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Anak-anak', 'Remaja', 'Dewasa', 'Orang Tua', 'Chinese/Tionghoa'] as $nama) {
            ExtrasCategory::firstOrCreate(['nama' => $nama]);
        }
    }
}
