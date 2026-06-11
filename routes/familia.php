<?php

use App\Http\Controllers\Familia\ActivitiesController;
use App\Http\Controllers\Familia\AuthController;
use App\Http\Controllers\Familia\ChildrenController;
use App\Http\Controllers\Familia\DashboardController;
use App\Http\Controllers\Familia\EnrollmentController;
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
    });
});
