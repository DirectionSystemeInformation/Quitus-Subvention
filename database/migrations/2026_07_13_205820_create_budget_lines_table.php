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
        Schema::create('budget_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->string('axe');
            $table->string('sous_axe_code');
            $table->string('sous_axe_label');
            $table->unsignedTinyInteger('numero_ligne');
            $table->string('designation')->nullable();
            $table->decimal('montant', 14, 2)->nullable();
            $table->string('contribution_partenaires')->nullable();
            $table->date('date')->nullable();
            $table->string('observations')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_lines');
    }
};
