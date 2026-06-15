<?php

use App\Http\Controllers\Familia\ActivitiesController;
use App\Http\Controllers\Familia\AuthController;
use App\Http\Controllers\Familia\ChildrenController;
use App\Http\Controllers\Familia\ConsentController;
use App\Http\Controllers\Familia\DashboardController;
use App\Http\Controllers\Familia\EnrollmentController;
use App\Http\Controllers\Familia\FormResponseController;
use App\Http\Controllers\Familia\FormsController;
use Illuminate\Support\Facades\Route;

Route::prefix('familia')->name('familia.')->group(function () {

    // Autenticación (accesible sin sesión)
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

    // Zona protegida
    Route::middleware('familia')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/hijos', ChildrenController::class)->name('children');
        Route::get('/extraescolares', [ActivitiesController::class, 'index'])->name('activities.index');
        Route::get('/extraescolares/{activity}', [ActivitiesController::class, 'show'])->name('activities.show');
        Route::post('/inscribir', [EnrollmentController::class, 'store'])->name('enroll');
        Route::post('/inscripciones/{enrollment}/cancelar', [EnrollmentController::class, 'cancel'])->name('enrollment.cancel');

        Route::get('/formularios', [FormsController::class, 'index'])->name('forms.index');
        Route::get('/formularios/{form}', [FormsController::class, 'show'])->name('forms.show');
        Route::post('/formularios/{form}/responder', [FormResponseController::class, 'store'])->name('forms.submit');
        Route::get('/formularios/{form}/respuestas/{response}', [FormResponseController::class, 'show'])->name('forms.responses.show');
        Route::get('/formularios/{form}/respuestas/{response}/editar', [FormResponseController::class, 'edit'])->name('forms.edit');
        Route::put('/formularios/{form}/respuestas/{response}', [FormResponseController::class, 'update'])->name('forms.update');

        Route::get('/consentimientos', [ConsentController::class, 'index'])->name('consents.index');
        Route::get('/consentimientos/{response}', [ConsentController::class, 'show'])->name('consents.show');
        Route::post('/consentimientos/{response}/aceptar', [ConsentController::class, 'aceptar'])->name('consents.accept');
        Route::post('/consentimientos/{response}/rechazar', [ConsentController::class, 'rechazar'])->name('consents.reject');
        Route::post('/consentimientos/{response}/revocar', [ConsentController::class, 'revocar'])->name('consents.revoke');
    });
});
