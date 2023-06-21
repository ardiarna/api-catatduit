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
        Schema::create('transaksis', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 2048);
            $table->timestamp('tanggal')->nullable();
            $table->enum('iskeluar', ['Y', 'N']);
            $table->integer('jumlah');
            $table->foreignId('kategori_id');
            $table->foreignId('rekening_id');
            $table->foreignId('parent_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};
