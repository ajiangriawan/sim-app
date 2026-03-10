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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained();
            $table->foreignId('rute_id')->constrained();
            $table->string('no_sjb')->unique();
            $table->date('tanggal');
            $table->double('tonase');
            $table->unsignedBigInteger('harga_tonase_pusat');
            $table->unsignedBigInteger('harga_tonase_vendor');
            $table->unsignedBigInteger('uang_jalan');
            $table->unsignedBigInteger('bonus_tonase');
            $table->unsignedBigInteger('uang_makan');
            $table->unsignedBigInteger('insentif')->nullable();
            $table->unsignedBigInteger('pendapatan_kotor');
            $table->unsignedBigInteger('pendapatan_bersih');
            $table->string('nama_deposit_pilihan')->nullable();
            $table->boolean('pakai_deposit')->default(true);
            $table->enum('status', ['selesai', 'batal'])->default('selesai');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
