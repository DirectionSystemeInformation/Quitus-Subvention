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
        Schema::create('campaign_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('criteres_scores')->nullable();
            $table->decimal('score_total', 5, 2)->nullable();
            $table->string('categorie')->nullable();
            $table->string('categorie_ajustee')->nullable();
            $table->decimal('montant_propose', 14, 2)->nullable();
            $table->decimal('montant_arbitre', 14, 2)->nullable();
            $table->decimal('montant_final', 14, 2)->nullable();
            $table->timestamp('quitus_delivered_at')->nullable();
            $table->string('quitus_reference')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_allocations');
    }
};
