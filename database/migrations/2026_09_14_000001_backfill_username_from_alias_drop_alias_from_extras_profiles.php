<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('extras_profiles as ep')
            ->join('users as u', 'u.id', '=', 'ep.user_id')
            ->whereNull('u.username')
            ->whereNotNull('ep.alias')
            ->select('ep.user_id', 'ep.alias')
            ->get()
            ->each(function ($row) {
                $base = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($row->alias));
                $base = trim(preg_replace('/_+/', '_', $base), '_') ?: 'extras_'.$row->user_id;
                $username = $base;
                $i = 2;
                while (DB::table('users')->where('username', $username)->exists()) {
                    $username = $base.'_'.$i++;
                }
                DB::table('users')->where('id', $row->user_id)->update(['username' => $username]);
            });

        Schema::table('extras_profiles', function (Blueprint $table) {
            $table->dropColumn('alias');
        });
    }

    public function down(): void
    {
        Schema::table('extras_profiles', function (Blueprint $table) {
            $table->string('alias')->nullable()->after('nama_asli');
        });
    }
};
