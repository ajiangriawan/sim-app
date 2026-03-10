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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_vendor')->default(false);
            $table->foreignId('vehicle_id')->constrained();
            $table->foreignId('workshop_id')->constrained();
            $table->string('no_invoice')->unique();
            $table->date('tanggal_service');
            $table->unsignedBigInteger('total_biaya');
            $table->boolean('pakai_deposit')->default(true);
            $table->string('nama_deposit_pilihan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
