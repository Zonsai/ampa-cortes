<?php

use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

// Front público del AMPA
Route::get('/', [PublicController::class, 'home'])->name('public.home');
Route::get('/ampa', [PublicController::class, 'about'])->name('public.about');
Route::get('/extraescolares', [PublicController::class, 'extracurriculars'])->name('public.extracurriculars');
Route::get('/anuncios', [PublicController::class, 'announcements'])->name('public.announcements.index');
Route::get('/anuncios/{announcement:slug}', [PublicController::class, 'announcement'])->name('public.announcements.show');
Route::get('/contacto', [PublicController::class, 'contact'])->name('public.contact');
