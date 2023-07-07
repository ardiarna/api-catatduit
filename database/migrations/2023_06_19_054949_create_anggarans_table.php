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
        Schema::create('anggarans', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('tahun');
            $table->smallInteger('bulan');
            $table->foreignId('kategori_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->integer('jumlah', false, true);
            $table->foreignId('parent_id');
            $table->timestamps();
            $table->unique(['tahun', 'bulan', 'kategori_id'], 'anggarans_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anggarans');
    }
};
