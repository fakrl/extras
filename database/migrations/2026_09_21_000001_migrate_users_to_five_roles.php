<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migrasi data pengguna yang masih memakai role lama
        DB::table('users')->whereIn('role', ['admin_default', 'admin_talco', 'admin_sosmed'])->update(['role' => 'admin']);
        DB::table('users')->where('role', 'admin_korlap')->update(['role' => 'korlap']);
        DB::table('users')->where('role', 'casting_director')->update(['role' => 'client']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin', 'korlap', 'client', 'extras') NOT NULL");
        } else {
            // SQLite support for changing column definition
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 30)->change();
            });
        }
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'admin')->update(['role' => 'admin_default']);
        DB::table('users')->where('role', 'korlap')->update(['role' => 'admin_korlap']);
        DB::table('users')->where('role', 'client')->update(['role' => 'casting_director']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin_default', 'admin_talco', 'admin_korlap', 'admin_sosmed', 'casting_director', 'extras') NOT NULL");
        }
    }
};
