<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Portail « Suivi des talents » : référentiels et rattachement aux comptes Quitus.
 *
 * Une fédération du suivi des talents est le prolongement d'un compte fédération de Quitus
 * (users.role = federation) : son nom et son e-mail restent gérés dans Quitus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talent_federations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('sigle', 20)->unique();
            $table->string('prefixe_licence', 10)->nullable();
            $table->string('president')->nullable();
            $table->string('telephone', 30)->nullable();
            $table->string('adresse')->nullable();
            $table->string('api_url')->nullable();             // API de vérification des licences
            $table->text('api_token')->nullable();             // chiffré
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::create('talent_disciplines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('federation_id')->nullable()->constrained('talent_federations')->nullOnDelete();
            $table->string('nom')->unique();
            $table->boolean('collectif')->default(false);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::create('talent_specialites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discipline_id')->constrained('talent_disciplines')->cascadeOnDelete();
            $table->string('nom');
            $table->timestamps();
            $table->unique(['discipline_id', 'nom']);
        });

        Schema::create('talent_referentiels', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50)->index();
            $table->string('valeur');
            $table->string('couleur', 20)->nullable();
            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->unique(['type', 'valeur']);
        });

        // Comptes complémentaires du suivi des talents (DTN, médecin, entraîneur...) : users.role = talents,
        // rattachés à une fédération par talent_federation_id, sans accès aux subventions.
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('talent_federation_id')->nullable()->after('federation_name')->constrained('talent_federations')->nullOnDelete();
            $table->string('telephone', 30)->nullable()->after('talent_federation_id');
            $table->string('fonction')->nullable()->after('telephone');
            $table->json('talent_preferences')->nullable()->after('fonction');
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('federation', 'dshn', 'admin', 'dg', 'comite_arbitrage', 'ministre', 'dgf', 'talents') NOT NULL DEFAULT 'federation'");
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("DELETE FROM users WHERE role = 'talents'");
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('federation', 'dshn', 'admin', 'dg', 'comite_arbitrage', 'ministre', 'dgf') NOT NULL DEFAULT 'federation'");
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('talent_federation_id');
            $table->dropColumn(['telephone', 'fonction', 'talent_preferences']);
        });
        Schema::dropIfExists('talent_referentiels');
        Schema::dropIfExists('talent_specialites');
        Schema::dropIfExists('talent_disciplines');
        Schema::dropIfExists('talent_federations');
    }
};
