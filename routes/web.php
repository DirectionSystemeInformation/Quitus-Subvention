<?php

use App\Http\Controllers\ActivityFormController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Dshn\ActivityLogController;
use App\Http\Controllers\Dshn\CanevasController;
use App\Http\Controllers\Dshn\DashboardController as DshnDashboardController;
use App\Http\Controllers\Dshn\FederationController;
use App\Http\Controllers\Dshn\ReportController as DshnReportController;
use App\Http\Controllers\Dshn\UserController as DshnUserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth as AuthFacade;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (AuthFacade::check()) {
        $user = AuthFacade::user();

        return redirect()->route(
            $user->isAdmin() ? 'admin.dashboard' : ($user->isDshn() ? 'dshn.dashboard' : 'dashboard')
        );
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

    Route::get('/rapport-activite/remplir', [ActivityFormController::class, 'create'])
        ->defaults('type', 'rapport_activite')
        ->name('rapport-activite.create');
    Route::post('/rapport-activite', [ActivityFormController::class, 'store'])
        ->defaults('type', 'rapport_activite')
        ->name('rapport-activite.store');
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
    Route::post('/federations/{federation}/valider', [FederationController::class, 'validate_'])->name('federations.validate');
    Route::post('/federations/valider-plusieurs', [FederationController::class, 'bulkValidate'])->name('federations.bulk-validate');
    Route::post('/federations/{federation}/rejeter', [FederationController::class, 'reject'])->name('federations.reject');
    Route::put('/federations/{federation}', [FederationController::class, 'update'])->name('federations.update');
    Route::delete('/federations/{federation}', [FederationController::class, 'destroy'])->name('federations.destroy');

    Route::get('/rapports', [DshnReportController::class, 'index'])->name('reports.index');
    Route::get('/rapports/{report}/telecharger', [DshnReportController::class, 'download'])->name('reports.download');
    Route::post('/rapports/{report}/valider', [DshnReportController::class, 'validate_'])->name('reports.validate');
    Route::post('/rapports/{report}/rejeter', [DshnReportController::class, 'reject'])->name('reports.reject');

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
