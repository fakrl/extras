<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // RF-32 / RF-47: add-on/reimburse manual (transport, penginapan, dst).
        // Polymorphic karena bisa nempel ke payments (Extras) ATAU
        // staff_payrolls (staf) — satu tabel shared, bukan duplikasi struktur
        // di dua tempat. Sifatnya optional & jumlahnya tidak tetap.
        Schema::create('payment_addons', function (Blueprint $table) {
            $table->id();
            $table->morphs('addable'); // addable_type, addable_id
            $table->string('label');
            $table->decimal('nominal', 12, 2);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_addons');
    }
};
