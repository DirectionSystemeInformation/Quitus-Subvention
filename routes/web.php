<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityFormController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Dgf\ActivityController as DgfActivityController;
use App\Http\Controllers\Dshn\ActivityLogController;
use App\Http\Controllers\Dshn\CampaignController;
use App\Http\Controllers\Dshn\CanevasController;
use App\Http\Controllers\Dshn\DashboardController as DshnDashboardController;
use App\Http\Controllers\Dshn\FederationController;
use App\Http\Controllers\Dshn\ReportController as DshnReportController;
use App\Http\Controllers\Dshn\SearchController as DshnSearchController;
use App\Http\Controllers\Dshn\UserController as DshnUserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth as AuthFacade;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (AuthFacade::check()) {
        return redirect()->route(home_route_name(AuthFacade::user()));
    }

    return view('landing');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register'])->name('register');

    Route::get('/mot-de-passe-oublie', [PasswordResetController::class, 'showRequestForm'])->name('password.request');
    Route::post('/mot-de-passe-oublie', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reinitialiser-mot-de-passe/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reinitialiser-mot-de-passe', [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'role:federation'])->group(function () {
    Route::get('/tableau-de-bord', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/documents', [ActivityFormController::class, 'index'])->name('documents.index');

    Route::get('/programme-budgetise/remplir', [ActivityFormController::class, 'create'])
        ->defaults('type', 'programme_budgetise')
        ->name('programme-budgetise.create');
    Route::post('/programme-budgetise', [ActivityFormController::class, 'store'])
        ->defaults('type', 'programme_budgetise')
        ->name('programme-budgetise.store');

    Route::get('/programme-reamenage/remplir', [ActivityFormController::class, 'create'])
        ->defaults('type', 'programme_reamenage')
        ->name('programme-reamenage.create');
    Route::post('/programme-reamenage', [ActivityFormController::class, 'store'])
        ->defaults('type', 'programme_reamenage')
        ->name('programme-reamenage.store');

    // Gestion des activités réalisées : chaque activité, une fois validée par la DGF,
    // se déverse automatiquement en ligne budgétaire dans le rapport d'activité de
    // l'année concernée (voir Dgf\ActivityController::validate_()). Le rapport
    // d'activité n'est donc plus rempli manuellement (routes rapport-activite.* retirées).
    Route::get('/activites', [ActivityController::class, 'index'])->name('activities.index');
    Route::get('/activites/nouvelle', [ActivityController::class, 'create'])->name('activities.create');
    Route::post('/activites', [ActivityController::class, 'store'])->name('activities.store');
    Route::get('/activites/{activity}', [ActivityController::class, 'show'])->name('activities.show');
    Route::get('/activites/{activity}/modifier', [ActivityController::class, 'edit'])->name('activities.edit');
    Route::put('/activites/{activity}', [ActivityController::class, 'update'])->name('activities.update');
    Route::post('/activites/{activity}/soumettre', [ActivityController::class, 'submit'])->name('activities.submit');
    Route::post('/activites/{activity}/pieces', [ActivityController::class, 'addDocument'])->name('activities.documents.store');
    Route::delete('/activites/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');
    Route::get('/activites/{activity}/pieces/{document}', [ActivityController::class, 'downloadDocument'])->name('activities.documents.download');
});

Route::get('/mes-documents/{report}', [ActivityFormController::class, 'show'])
    ->middleware('auth')
    ->name('activity-form.show');

Route::middleware('auth')->group(function () {
    Route::get('/mon-profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/mon-profil', [ProfileController::class, 'update'])->name('profile.update');
});

// Routes de "back-office" partagées par les rôles DSHN et Admin : mêmes contrôleurs,
// mêmes vues, mais chaque rôle dispose de son propre préfixe et de ses propres noms de
// route (/dshn/... => dshn.*, /admin/... => admin.*). Voir app/helpers.php::role_route().
$backOfficeRoutes = function () {
    Route::get('/', [DshnDashboardController::class, 'index'])->name('dashboard');

    Route::get('/federations', [FederationController::class, 'index'])->name('federations.index');
    Route::get('/federations/{federation}', [FederationController::class, 'show'])->name('federations.show');
    Route::post('/federations/{federation}/valider', [FederationController::class, 'validate_'])->name('federations.validate');
    Route::post('/federations/valider-plusieurs', [FederationController::class, 'bulkValidate'])->name('federations.bulk-validate');
    Route::post('/federations/{federation}/rejeter', [FederationController::class, 'reject'])->name('federations.reject');
    Route::put('/federations/{federation}', [FederationController::class, 'update'])->name('federations.update');
    Route::delete('/federations/{federation}', [FederationController::class, 'destroy'])->name('federations.destroy');

    Route::get('/rapports', [DshnReportController::class, 'index'])->name('reports.index');
    Route::get('/rapports/{report}/telecharger', [DshnReportController::class, 'download'])->name('reports.download');
    Route::post('/rapports/{report}/valider', [DshnReportController::class, 'validate_'])->name('reports.validate');
    Route::post('/rapports/{report}/rejeter', [DshnReportController::class, 'reject'])->name('reports.reject');

    Route::get('/recherche', [DshnSearchController::class, 'index'])->name('search.index');

    Route::get('/canevas', [CanevasController::class, 'index'])->name('canevas.index');
    Route::post('/canevas/axes', [CanevasController::class, 'storeAxe'])->name('canevas.axes.store');
    Route::put('/canevas/axes/{axe}', [CanevasController::class, 'updateAxe'])->name('canevas.axes.update');
    Route::delete('/canevas/axes/{axe}', [CanevasController::class, 'destroyAxe'])->name('canevas.axes.destroy');
    Route::post('/canevas/axes/{axe}/sous-axes', [CanevasController::class, 'storeSousAxe'])->name('canevas.sous-axes.store');
    Route::put('/canevas/sous-axes/{sousAxe}', [CanevasController::class, 'updateSousAxe'])->name('canevas.sous-axes.update');
    Route::delete('/canevas/sous-axes/{sousAxe}', [CanevasController::class, 'destroySousAxe'])->name('canevas.sous-axes.destroy');
};

Route::middleware(['auth', 'role:dshn'])->prefix('dshn')->name('dshn.')->group($backOfficeRoutes);

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () use ($backOfficeRoutes) {
    $backOfficeRoutes();

    Route::get('/historique', [ActivityLogController::class, 'index'])->name('activity-log.index');

    Route::get('/comptes', [DshnUserController::class, 'index'])->name('users.index');
    Route::post('/comptes', [DshnUserController::class, 'store'])->name('users.store');
    Route::put('/comptes/{user}', [DshnUserController::class, 'update'])->name('users.update');
    Route::delete('/comptes/{user}', [DshnUserController::class, 'destroy'])->name('users.destroy');
});

// Circuit de répartition budgétaire (étapes 3 à 12 du workflow officiel) — partagé
// entre DSHN/admin et les 3 acteurs du circuit d'arbitrage (DG, Comité, Ministre) ;
// chaque action vérifie en plus le rôle exact attendu dans le contrôleur.
Route::middleware(['auth', 'role:dshn,admin,dg,comite_arbitrage,ministre'])
    ->prefix('campagnes')->name('campagnes.')
    ->group(function () {
        Route::get('/', [CampaignController::class, 'index'])->name('index');
        Route::post('/', [CampaignController::class, 'store'])->name('store');
        Route::get('/{campaign}', [CampaignController::class, 'show'])->name('show');

        Route::post('/{campaign}/traitement', [CampaignController::class, 'advanceToPonderation'])->name('traitement');
        Route::post('/{campaign}/ponderation', [CampaignController::class, 'updatePonderation'])->name('ponderation.update');
        Route::post('/{campaign}/ponderation/confirmer', [CampaignController::class, 'confirmPonderation'])->name('ponderation.confirm');
        Route::post('/{campaign}/categorisation/confirmer', [CampaignController::class, 'advanceToRepartition'])->name('categorisation.confirm');
        Route::post('/{campaign}/repartition', [CampaignController::class, 'updateRepartition'])->name('repartition.update');
        Route::post('/{campaign}/soumettre-dg', [CampaignController::class, 'submitToDg'])->name('submit-dg');

        Route::post('/{campaign}/dg/valider', [CampaignController::class, 'dgValidate'])->name('dg.validate');
        Route::post('/{campaign}/dg/rejeter', [CampaignController::class, 'dgReject'])->name('dg.reject');

        Route::post('/{campaign}/arbitrage', [CampaignController::class, 'updateArbitrage'])->name('arbitrage.update');
        Route::post('/{campaign}/arbitrage/finaliser', [CampaignController::class, 'finalizeArbitrage'])->name('arbitrage.finalize');

        Route::post('/{campaign}/ministre/valider', [CampaignController::class, 'ministreValidate'])->name('ministre.validate');
        Route::post('/{campaign}/ministre/rejeter', [CampaignController::class, 'ministreReject'])->name('ministre.reject');

        Route::post('/{campaign}/session', [CampaignController::class, 'organizeSession'])->name('session.organize');

        Route::post('/{campaign}/federations/{federation}/quitus', [CampaignController::class, 'deliverQuitus'])->name('quitus.deliver');
    });

// Le téléchargement est aussi accessible à la fédération propriétaire ;
// le contrôleur vérifie l'appartenance et la délivrance effective du quitus.
Route::get('/campagnes/{campaign}/federations/{federation}/quitus', [CampaignController::class, 'downloadQuitus'])
    ->middleware('auth')
    ->name('campagnes.quitus.download');

// Vérification des activités réalisées par les fédérations, avant leur déversement
// dans le rapport d'activité (voir Dgf\ActivityController). L'admin y a aussi accès
// pour supervision, comme pour le reste du back-office.
Route::middleware(['auth', 'role:dgf,admin'])->prefix('dgf')->name('dgf.')->group(function () {
    Route::get('/activites', [DgfActivityController::class, 'index'])->name('activities.index');
    Route::get('/activites/{activity}', [DgfActivityController::class, 'show'])->name('activities.show');
    Route::post('/activites/{activity}/valider', [DgfActivityController::class, 'validate_'])->name('activities.validate');
    Route::post('/activites/{activity}/rejeter', [DgfActivityController::class, 'reject'])->name('activities.reject');
    Route::get('/activites/{activity}/pieces/{document}', [DgfActivityController::class, 'downloadDocument'])->name('activities.documents.download');
});
