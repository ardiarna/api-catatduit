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
        Schema::create('piutang_detils', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 2048);
            $table->timestamp('tanggal')->nullable();
            $table->enum('isbayar', ['Y', 'N']);
            $table->integer('jumlah', false, true);
            $table->foreignId('piutang_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('rekening_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('transaksi_id')->nullable()->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('piutang_detils');
    }
};
