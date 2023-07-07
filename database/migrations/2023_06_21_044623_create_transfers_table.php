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
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 2048)->nullable();
            $table->timestamp('tanggal')->nullable();
            $table->integer('jumlah', false, true);
            $table->foreignId('rekasal_id')->constrained('rekenings')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('rektuju_id')->constrained('rekenings')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('parent_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
