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
        Schema::create('pinjamans', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 2048);
            $table->timestamp('tanggal')->nullable();
            $table->timestamp('tempo')->nullable();
            $table->string('keterangan', 2048)->nullable();
            $table->integer('jumlah', false, true);
            $table->integer('bayar', false, true);
            $table->foreignId('parent_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pinjamans');
    }
};
