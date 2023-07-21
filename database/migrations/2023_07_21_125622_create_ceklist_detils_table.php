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
        Schema::create('ceklist_detils', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 2048);
            $table->enum('isceklist', ['Y', 'N']);
            $table->enum('isaktif', ['Y', 'N']);
            $table->foreignId('ceklist_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ceklist_detils');
    }
};
