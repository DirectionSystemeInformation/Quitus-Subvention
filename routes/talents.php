<?php

/*
 * Portail « Suivi des talents » (/suivi-talents, routes suivi.*).
 * Même authentification que Quitus ; l'accès est contrôlé par le middleware portail.talents
 * puis, écran par écran, par les permissions talents (spatie/laravel-permission).
 */

use App\Http\Controllers\Talents\Admin\DisciplineController;
use App\Http\Controllers\Talents\Admin\JournalController;
use App\Http\Controllers\Talents\Admin\ReferentielController;
use App\Http\Controllers\Talents\Admin\RoleController;
use App\Http\Controllers\Talents\Admin\UtilisateurController;
use App\Http\Controllers\Talents\DashboardController;
use App\Http\Controllers\Talents\Modules;
use App\Http\Controllers\Talents\RapportController;
use App\Http\Controllers\Talents\TalentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'portail.talents'])->prefix('suivi-talents')->name('suivi.')->group(function () {
    // Tableau de bord
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/tableau-de-bord/preferences', [DashboardController::class, 'preferences'])->name('dashboard.preferences');

    // Talents : fiche centrale + circuit de validation
    Route::get('/talents/validations', [TalentController::class, 'validations'])->name('talents.validations');
    Route::post('/talents/{talent}/soumettre', [TalentController::class, 'soumettre'])->name('talents.soumettre');
    Route::post('/talents/{talent}/valider', [TalentController::class, 'valider'])->name('talents.valider');
    Route::post('/talents/{talent}/rejeter', [TalentController::class, 'rejeter'])->name('talents.rejeter');
    Route::get('/talents/{talent}/imprimer', [TalentController::class, 'imprimer'])->name('talents.imprimer');
    Route::resource('talents', TalentController::class);

    // Modules de suivi (un écran par module)
    $modules = [
        'licences' => Modules\LicenceController::class,
        'competitions' => Modules\CompetitionController::class,
        'performances' => Modules\PerformanceController::class,
        'evaluations' => Modules\EvaluationPhysiqueController::class,
        'medical' => Modules\SuiviMedicalController::class,
        'parcours' => Modules\ParcoursController::class,
        'bourses' => Modules\BourseController::class,
        'reconversions' => Modules\ReconversionController::class,
        'entraineurs' => Modules\EntraineurController::class,
    ];
    foreach ($modules as $nom => $controleur) {
        Route::resource($nom, $controleur)->except('show')->parameters([$nom => 'id']);
    }
    // Les fédérations sont créées et validées dans Quitus : ici, seulement leurs réglages talents.
    Route::resource('federations', Modules\FederationController::class)->only(['index', 'edit', 'update'])->parameters(['federations' => 'id']);
    Route::post('/licences/{licence}/verifier', [Modules\LicenceController::class, 'verifier'])->name('licences.verifier');
    Route::post('/bourses/{bourse}/decider', [Modules\BourseController::class, 'decider'])->name('bourses.decider');

    // Pilotage
    Route::get('/rapports', [RapportController::class, 'index'])->name('rapports.index');
    Route::get('/journal', [JournalController::class, 'index'])->name('journal.index');

    // Administration
    Route::resource('disciplines', DisciplineController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('/disciplines/{discipline}/specialites', [DisciplineController::class, 'ajouterSpecialite'])->name('specialites.store');
    Route::delete('/specialites/{specialite}', [DisciplineController::class, 'supprimerSpecialite'])->name('specialites.destroy');
    Route::resource('referentiels', ReferentielController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('utilisateurs', UtilisateurController::class)->except('show');
    Route::resource('roles', RoleController::class)->only(['index', 'store', 'update', 'destroy']);
});
