<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Une fédération ne saisit que pour l'année ouverte de droit (activités :
     * année en cours ; programmes : N+1). L'administration peut rouvrir
     * exceptionnellement une autre année, pour un type de saisie donné.
     */
    public function up(): void
    {
        Schema::create('ouvertures_saisie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->unsignedSmallInteger('year');
            $table->string('motif', 255);
            $table->date('expires_on')->nullable();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'type', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ouvertures_saisie');
    }
};
