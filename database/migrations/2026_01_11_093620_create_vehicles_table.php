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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nama_deposit_pilihan')->nullable();
            $table->string('partai');
            $table->string('no_lambung')->unique();
            $table->string('no_plat')->unique();
            $table->string('kapasitas');
            $table->year('tahun');
            // $table->boolean('is_vendor')->default(false); // Mengganti enum true/false menjadi boolean
            $table->enum('status', ['aktif', 'perbaikan', 'nonaktif'])->default('aktif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
