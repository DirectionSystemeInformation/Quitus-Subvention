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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('annee_n1')->unique();
            $table->unsignedTinyInteger('etape')->default(3);
            $table->enum('statut', ['en_cours', 'termine'])->default('en_cours');
            $table->enum('dg_decision', ['valide', 'rejete'])->nullable();
            $table->timestamp('dg_decided_at')->nullable();
            $table->string('dg_rejection_reason')->nullable();
            $table->enum('ministre_decision', ['valide', 'rejete'])->nullable();
            $table->timestamp('ministre_decided_at')->nullable();
            $table->string('ministre_rejection_reason')->nullable();
            $table->date('session_arbitrage_date')->nullable();
            $table->timestamp('session_arbitrage_organized_at')->nullable();
            $table->json('bareme_repartition')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
