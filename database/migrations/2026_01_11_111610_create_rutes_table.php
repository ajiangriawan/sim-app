<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rutes', function (Blueprint $table) {
            $table->id();
            $table->string('nama_rute');
            $table->integer('jarak'); // Menggunakan integer untuk perhitungan matematika
            $table->unsignedBigInteger('harga_tonase_pusat');
            $table->unsignedBigInteger('harga_tonase_vendor');
            $table->unsignedBigInteger('gaji_pokok');
            $table->unsignedBigInteger('uang_jalan');
            $table->unsignedBigInteger('uang_makan');
            $table->unsignedBigInteger('insentif')->nullable();
            $table->unsignedBigInteger('bahan_bakar');
            $table->unsignedBigInteger('pungli');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rutes');
    }
};
