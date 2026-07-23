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
        Schema::create('canvas_sous_axes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('canvas_axe_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('label');
            $table->unsignedTinyInteger('lignes_count')->default(1);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('canvas_sous_axes');
    }
};
