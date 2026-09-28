<?php

namespace Database\Seeders;

use App\Models\ExtrasCategory;
use Illuminate\Database\Seeder;

class ExtrasCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (ExtrasCategory::GRUP as $grup => $tags) {
            foreach ($tags as $nama) {
                ExtrasCategory::updateOrCreate(['nama' => $nama], ['grup' => $grup]);
            }
        }
    }
}
