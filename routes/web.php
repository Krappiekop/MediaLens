<?php

use App\Http\Controllers\ArtikelController;
use App\Http\Controllers\GebeurtenisController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OverController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])
    ->name('home');

Route::get('/gebeurtenissen', [GebeurtenisController::class, 'index'])
    ->name('gebeurtenissen.index');

Route::get('/onderwerpen/{categorie}', [GebeurtenisController::class, 'index'])
    ->name('gebeurtenissen.categorie');

Route::get('/artikelen', [ArtikelController::class, 'index'])
    ->name('artikelen.index');

Route::get('/gebeurtenissen/{gebeurtenis}', [GebeurtenisController::class, 'show'])
    ->name('gebeurtenissen.show');

Route::get('/over', [OverController::class, 'index'])
    ->name('over');
