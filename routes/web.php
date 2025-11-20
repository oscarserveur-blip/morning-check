<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\MailingController;
use App\Http\Controllers\CheckController;
use App\Http\Controllers\ServiceCheckController;
use App\Http\Controllers\RappelDestinataireController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified', 'force.password.change'])->name('dashboard');

Route::middleware(['auth', 'force.password.change'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Routes pour la gestion des clients
    Route::resource('clients', ClientController::class);
    Route::post('/clients/{client}/checks/auto', [ClientController::class, 'autoCheck'])->name('clients.auto-check');
    Route::get('/clients/{client}/duplicate', [\App\Http\Controllers\ClientController::class, 'duplicate'])->name('clients.duplicate');
    Route::get('/clients/categories', [ClientController::class, 'getCategories'])->name('clients.categories');

    // Routes pour les catégories
    Route::get('/categories/{category}/services', [CategoryController::class, 'getServices'])->name('categories.services');
    Route::resource('categories', CategoryController::class);

    // Routes pour les services
    Route::resource('services', ServiceController::class);

    // Routes pour les mailings
    Route::resource('mailings', MailingController::class);

    // Routes pour les destinataires de rappels
    Route::resource('rappel-destinataires', RappelDestinataireController::class);

    // Routes pour les checks
    Route::resource('checks', CheckController::class);
    Route::get('/checks/{check}/export', [CheckController::class, 'export'])->name('checks.export');
    Route::get('/checks/{check}/status', [CheckController::class, 'checkStatus'])->name('checks.status');
    Route::post('/checks/{check}/send', [CheckController::class, 'send'])->name('checks.send');

    // Routes pour les service checks
    Route::resource('service-checks', ServiceCheckController::class);

    // Routes pour les services d'un check
    Route::get('/checks/{check}/services', [ServiceCheckController::class, 'getCheckServices'])->name('checks.services');
    Route::put('/checks/{check}/service-checks', [ServiceCheckController::class, 'updateAll'])->name('checks.service-checks.update-all');
    Route::post('/service-checks/{serviceCheck}/status', [ServiceCheckController::class, 'updateStatus'])->name('service-checks.update-status');
    Route::post('/service-checks/{serviceCheck}/comment', [ServiceCheckController::class, 'updateComment'])->name('service-checks.update-comment');
    Route::post('/service-checks/{serviceCheck}/intervenant', [ServiceCheckController::class, 'updateIntervenant'])->name('service-checks.update-intervenant');

    // Ajout/suppression de service à un check
    Route::post('/checks/{check}/service-checks', [ServiceCheckController::class, 'store'])->name('checks.service-checks.store');

    // Routes pour les templates
    Route::resource('templates', TemplateController::class);
    Route::get('/templates/{template}/duplicate', [\App\Http\Controllers\TemplateController::class, 'duplicate'])->name('templates.duplicate');
    Route::get('/templates/{template}/export-example', [\App\Http\Controllers\TemplateController::class, 'exportExample'])->name('templates.exportExample');

    // ... existing code ...
    Route::get('/clients/{client}/checks-list', [App\Http\Controllers\ClientController::class, 'checksList'])->name('clients.checks-list');
    // ... existing code ...

    Route::get('/test-mail', function () {
        Mail::raw('Ceci est un test', function ($message) {
            $message->to('test@example.com') // Peu importe l'adresse, Mailtrap va le capturer
                    ->subject('Test CHECK DU MATIN');
        });
    
        return 'Mail envoyé !';
    });
});

Route::middleware(['auth', 'force.password.change'])->group(function () {
    Route::get('/test', [App\Http\Controllers\TestController::class, 'test'])->name('test');
    Route::get('/test-user', [App\Http\Controllers\TestController::class, 'testUser'])->name('test.user');
    Route::resource('users', App\Http\Controllers\UserController::class);
});

require __DIR__.'/auth.php';
