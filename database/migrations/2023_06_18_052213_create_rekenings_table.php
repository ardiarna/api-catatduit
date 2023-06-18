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
        Schema::create('rekenings', function (Blueprint $table) {
            $table->id();
            $table->enum('jenis', ['K', 'D', 'C', 'M', 'W']);
            $table->string('nama');
            $table->integer('saldo');
            $table->foreignId('bank_id')->nullable();
            $table->integer('saldo_endap')->nullable();
            $table->string('keterangan', 2048)->nullable();
            $table->foreignId('parent_id');
            $table->timestamps();
            $table->unique(['nama', 'parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekenings');
    }
};
