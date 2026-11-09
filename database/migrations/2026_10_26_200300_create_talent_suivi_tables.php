<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fédération propriétaire ; null = calendrier national (Ministère).
        Schema::create('talent_competitions', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->foreignId('federation_id')->nullable()->constrained('talent_federations')->nullOnDelete();
            $table->foreignId('discipline_id')->nullable()->constrained('talent_disciplines')->nullOnDelete();
            $table->string('niveau');
            $table->string('lieu')->nullable();
            $table->string('pays')->nullable();
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->timestamps();
        });

        // Onglet 2 : performances
        Schema::create('talent_performances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->foreignId('competition_id')->constrained('talent_competitions')->cascadeOnDelete();
            $table->date('date_evenement');
            $table->string('epreuve')->nullable();
            $table->string('performance')->nullable();
            $table->string('rang')->nullable();
            $table->string('distinction')->nullable();
            $table->boolean('minima_atteint')->default(false);
            $table->text('observations_dtn')->nullable();
            $table->foreignId('saisi_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Onglet 3 : évaluations physiques
        Schema::create('talent_evaluations_physiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->date('date_evaluation');
            $table->decimal('taille_cm', 5, 1)->nullable();
            $table->decimal('poids_kg', 5, 1)->nullable();
            $table->decimal('vma_kmh', 4, 1)->nullable();
            $table->decimal('detente_cm', 5, 1)->nullable();
            $table->decimal('sprint_30m_s', 4, 2)->nullable();
            $table->json('autres_tests')->nullable();
            $table->string('evaluateur')->nullable();
            $table->text('avis')->nullable();
            $table->foreignId('saisi_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Onglet 3 : suivi médical (confidentiel)
        Schema::create('talent_suivis_medicaux', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->date('date_consultation');
            $table->string('statut_medical');
            $table->string('type_blessure')->nullable();
            $table->string('localisation')->nullable();
            $table->date('date_blessure')->nullable();
            $table->date('date_retour_prevue')->nullable();
            $table->unsignedInteger('jours_indisponibilite')->default(0);
            $table->boolean('cloture')->default(false);
            $table->string('medecin')->nullable();
            $table->text('avis_medical')->nullable();
            $table->foreignId('saisi_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Onglet 4 : parcours & carrière
        Schema::create('talent_parcours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->string('saison', 20);
            $table->string('club');
            $table->string('pays')->default('Burkina Faso');
            $table->string('statut_contractuel')->nullable();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->foreignId('entraineur_id')->nullable()->constrained('talent_entraineurs')->nullOnDelete();
            $table->string('niveau_scolaire')->nullable();
            $table->text('commentaires')->nullable();
            $table->timestamps();
        });

        // Onglet 4 : bourses et appuis de l'État
        Schema::create('talent_bourses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->string('type_bourse');
            $table->string('financeur')->default('Ministère des Sports');
            $table->decimal('montant', 14, 0)->nullable();
            $table->string('devise', 5)->default('XOF');
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->string('statut')->default('Demandée');
            $table->text('observations')->nullable();
            $table->foreignId('decide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decide_le')->nullable();
            $table->timestamps();
        });

        // Onglet 5 : retraite & reconversion
        Schema::create('talent_reconversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->unique()->constrained('talents')->cascadeOnDelete();
            $table->unsignedSmallInteger('annee_fin_carriere');
            $table->string('motif_retraite');
            $table->string('niveau_etudes')->nullable();
            $table->string('projet')->nullable();
            $table->string('domaine')->nullable();
            $table->string('statut_accompagnement')->default('En attente');
            $table->string('role_actuel')->nullable();
            $table->string('programme_appui')->nullable();
            $table->text('besoins')->nullable();
            $table->timestamps();
        });

        // Journal propre au portail talents (Quitus garde son propre activity_logs)
        Schema::create('talent_journal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 30);
            $table->string('module', 50);
            $table->unsignedBigInteger('objet_id')->nullable();
            $table->string('libelle')->nullable();
            $table->json('modifications')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['talent_journal', 'talent_reconversions', 'talent_bourses', 'talent_parcours', 'talent_suivis_medicaux',
            'talent_evaluations_physiques', 'talent_performances', 'talent_competitions'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
