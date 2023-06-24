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
        Schema::create('adjusts', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->timestamp('tanggal')->nullable();
            $table->enum('iskeluar', ['Y', 'N']);
            $table->integer('jumlah', false, true);
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
        Schema::dropIfExists('adjusts');
    }
};
