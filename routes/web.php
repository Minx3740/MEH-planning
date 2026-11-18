<?php
 
use App\Http\Controllers\AccountRequestController;
use App\Http\Controllers\Admin\AccountRequestController as AdminAccountRequestController;
use App\Http\Controllers\Admin\DirectionController as AdminDirectionController;
use App\Http\Controllers\Admin\GroupController as AdminGroupController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

Route::get('/accueil', static fn (): View => view('welcome'))
    ->name('public.home');

Route::middleware('guest')->group(function () {
    Route::get('/connexion', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('/connexion', [AuthenticatedSessionController::class, 'store'])
        ->name('login.store');

    Route::get('/demande-compte', [AccountRequestController::class, 'create'])
        ->name('account-requests.create');

    Route::post('/demande-compte', [AccountRequestController::class, 'store'])
        ->name('account-requests.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/sessions', [AuthenticatedSessionController::class, 'index'])
        ->name('sessions.index');

    Route::post('/sessions/{userSession}/revoke', [AuthenticatedSessionController::class, 'revoke'])
        ->name('sessions.revoke');

    Route::delete('/sessions/{userSession}', [AuthenticatedSessionController::class, 'revoke'])
        ->name('sessions.destroy');

    Route::post('/deconnexion', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    Route::get('/', function (Request $request): RedirectResponse {
        $user = $request->user();

        return match (true) {
            Gate::forUser($user)->allows('admin') => redirect()->route('admin.dashboard'),
            Gate::forUser($user)->allows('sg') => redirect()->route('sg.dashboard'),
            Gate::forUser($user)->allows('director') => redirect()->route('director.dashboard'),
            default => abort(403),
        };
    })->name('dashboard');


    Route::get('/sg', function (Request $request): View {
        return view('dashboard.role-home', [
            'user' => $request->user(),
            'pageTitle' => 'Accueil du Secrétariat général',
            'roleLabel' => 'Secrétariat général',
        ]);
    })->middleware('can:sg')->name('sg.dashboard');

    Route::get('/directeur', function (Request $request): View {
        return view('dashboard.role-home', [
            'user' => $request->user(),
            'pageTitle' => 'Accueil de la direction',
            'roleLabel' => 'Directeur',
        ]);
    })->middleware('can:director')->name('director.dashboard');
});

Route::middleware(['auth', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function (Request $request): View {
        return view('admin.dashboard', [
            'user' => $request->user(),
        ]);
    })->name('dashboard');

    Route::get('/groups', [AdminGroupController::class, 'index'])->name('groups.index');
    Route::get('/groups/create', [AdminGroupController::class, 'create'])->name('groups.create');
    Route::post('/groups', [AdminGroupController::class, 'store'])->name('groups.store');
    Route::get('/groups/{group}/edit', [AdminGroupController::class, 'edit'])->name('groups.edit');
    Route::put('/groups/{group}', [AdminGroupController::class, 'update'])->name('groups.update');

    Route::get('/directions', [AdminDirectionController::class, 'index'])->name('directions.index');
    Route::get('/directions/create', [AdminDirectionController::class, 'create'])->name('directions.create');
    Route::post('/directions', [AdminDirectionController::class, 'store'])->name('directions.store');
    Route::get('/directions/{direction}/edit', [AdminDirectionController::class, 'edit'])->name('directions.edit');
    Route::put('/directions/{direction}', [AdminDirectionController::class, 'update'])->name('directions.update');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/activate', [AdminUserController::class, 'activate'])->name('users.activate');
    Route::patch('/users/{user}/deactivate', [AdminUserController::class, 'deactivate'])->name('users.deactivate');
    Route::patch('/users/{user}/revoke-sessions', [AdminUserController::class, 'revokeSessions'])->name('users.revoke-sessions');

    Route::get('/account-requests', [AdminAccountRequestController::class, 'index'])->name('account-requests.index');
    Route::get('/account-requests/{accountRequest}', [AdminAccountRequestController::class, 'show'])->name('account-requests.show');
    Route::post('/account-requests/{accountRequest}/approve', [AdminAccountRequestController::class, 'approve'])->name('account-requests.approve');
    Route::post('/account-requests/{accountRequest}/reject', [AdminAccountRequestController::class, 'reject'])->name('account-requests.reject');
});


// Exercice de route
Route::get('/exercice/connexion',[App\Http\Controllers\ExerciceConnexionController::class,'formulaire'])->name('exercice.connexion');
Route::post('/exercice/connexion',[App\Http\Controllers\ExerciceConnexionController::class,'connecter'])->name('exercice.connecter');
Route::get('/exercice/accueil',[App\Http\Controllers\ExerciceConnexionController::class,'accueil'])->name('exercice.accueil');
Route::post('/exercice/deconnexion',[App\Http\Controllers\ExerciceConnexionController::class,'deconnecter'])->name('exercice.deconnexion');
Route::post('/exercice/rechercher',[App\Http\Controllers\ExerciceConnexionController::class,'rechercher'])->name('exercice.rechercher');

