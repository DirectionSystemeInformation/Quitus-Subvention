<?php

use App\Http\Controllers\ActivityFormController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Dshn\FederationController;
use App\Http\Controllers\Dshn\ReportController as DshnReportController;
use Illuminate\Support\Facades\Auth as AuthFacade;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (AuthFacade::check()) {
        return AuthFacade::user()->isDshn()
            ? redirect()->route('dshn.federations.index')
            : redirect()->route('dashboard');
    }

    return view('landing');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register'])->name('register');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'role:federation'])->group(function () {
    Route::get('/tableau-de-bord', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/programme-budgetise', [ActivityFormController::class, 'create'])
        ->defaults('type', 'programme_budgetise')
        ->name('programme-budgetise.create');
    Route::post('/programme-budgetise', [ActivityFormController::class, 'store'])
        ->defaults('type', 'programme_budgetise')
        ->name('programme-budgetise.store');

    Route::get('/rapport-activite', [ActivityFormController::class, 'create'])
        ->defaults('type', 'rapport_activite')
        ->name('rapport-activite.create');
    Route::post('/rapport-activite', [ActivityFormController::class, 'store'])
        ->defaults('type', 'rapport_activite')
        ->name('rapport-activite.store');
});

Route::get('/mes-documents/{report}', [ActivityFormController::class, 'show'])
    ->middleware('auth')
    ->name('activity-form.show');

Route::middleware(['auth', 'role:dshn'])->prefix('dshn')->name('dshn.')->group(function () {
    Route::get('/federations', [FederationController::class, 'index'])->name('federations.index');
    Route::post('/federations/{federation}/valider', [FederationController::class, 'validate_'])->name('federations.validate');
    Route::post('/federations/{federation}/rejeter', [FederationController::class, 'reject'])->name('federations.reject');

    Route::get('/rapports', [DshnReportController::class, 'index'])->name('reports.index');
    Route::get('/rapports/{report}/telecharger', [DshnReportController::class, 'download'])->name('reports.download');
    Route::post('/rapports/{report}/valider', [DshnReportController::class, 'validate_'])->name('reports.validate');
    Route::post('/rapports/{report}/rejeter', [DshnReportController::class, 'reject'])->name('reports.reject');
});
