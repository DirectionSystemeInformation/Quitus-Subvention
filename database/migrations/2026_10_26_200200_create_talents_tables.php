<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talent_entraineurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenoms');
            $table->foreignId('discipline_id')->nullable()->constrained('talent_disciplines')->nullOnDelete();
            $table->foreignId('federation_id')->nullable()->constrained('talent_federations')->nullOnDelete();
            $table->string('diplome')->nullable();
            $table->string('structure')->nullable();
            $table->string('pays')->default('Burkina Faso');
            $table->string('telephone', 30)->nullable();
            $table->string('email')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        // Fiche d'identification (onglet 1 du canevas)
        Schema::create('talents', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();          // TLT-SIGLE-AAAA-NNN
            $table->string('nom');
            $table->string('prenoms');
            $table->date('date_naissance');
            $table->string('lieu_naissance')->nullable();
            $table->enum('genre', ['Masculin', 'Féminin']);
            $table->string('nationalites')->default('Burkinabè');
            $table->string('region')->nullable();
            $table->string('telephone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('photo')->nullable();
            $table->foreignId('federation_id')->constrained('talent_federations');
            $table->foreignId('discipline_id')->constrained('talent_disciplines');
            $table->string('specialite')->nullable();
            $table->foreignId('entraineur_id')->nullable()->constrained('talent_entraineurs')->nullOnDelete();
            $table->string('statut_carriere')->default('Actif');
            $table->string('statut_profil')->default('Brouillon'); // Brouillon, En attente API, En attente validation, Validé, Rejeté
            $table->text('motif_rejet')->nullable();
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_le')->nullable();
            $table->boolean('haut_potentiel')->default(false);
            $table->text('observations')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('talent_licences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->string('type_licence');
            $table->enum('portee', ['Nationale', 'Internationale']);
            $table->string('numero');
            $table->string('organisme');
            $table->string('club')->nullable();
            $table->string('pays_club')->nullable();
            $table->date('date_delivrance')->nullable();
            $table->date('date_expiration')->nullable();
            $table->string('statut')->default('Active');
            $table->string('verification_api')->default('Non vérifiée');
            $table->timestamp('verifie_le')->nullable();
            $table->timestamps();
            $table->unique(['organisme', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talent_licences');
        Schema::dropIfExists('talents');
        Schema::dropIfExists('talent_entraineurs');
    }
};
