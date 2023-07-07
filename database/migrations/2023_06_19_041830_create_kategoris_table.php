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
        Schema::create('kategoris', function (Blueprint $table) {
            $table->id();
            $table->enum('jenis', ['M', 'K']);
            $table->string('nama');
            $table->string('ikon');
            $table->foreignId('rekening_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('parent_id');
            $table->timestamps();
            $table->unique(['jenis', 'nama', 'parent_id'], 'kategoris_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kategoris');
    }
};
