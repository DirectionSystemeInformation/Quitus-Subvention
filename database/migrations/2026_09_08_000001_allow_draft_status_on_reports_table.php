<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->enum('status', ['brouillon', 'soumis', 'valide', 'rejete'])->default('soumis')->change();
        });
    }

    public function down(): void
    {
        // Un retour arrière ne doit ni supprimer ni soumettre les brouillons existants.
        if (DB::table('reports')->where('status', 'brouillon')->exists()) {
            throw new RuntimeException('Des brouillons existent. Exportez-les ou terminez leur traitement avant de retirer ce statut.');
        }

        Schema::table('reports', function (Blueprint $table) {
            $table->enum('status', ['soumis', 'valide', 'rejete'])->default('soumis')->change();
        });
    }
};
