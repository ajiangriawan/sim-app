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
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->string('nama_pihak'); // Bisa nama vendor atau orang
            $table->date('tanggal_deposit');
            $table->unsignedBigInteger('jumlah_deposit');
            $table->text('keterangan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained(); // Siapa yang input
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};
