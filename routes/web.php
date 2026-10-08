<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GebeurtenisController;

Route::get('/', [GebeurtenisController::class, 'index']);
Route::get('/gebeurtenissen', [GebeurtenisController::class, 'index'])
    ->name('gebeurtenissen.index');

Route::get('/gebeurtenissen/{gebeurtenis}', [GebeurtenisController::class, 'show'])
    ->name('gebeurtenissen.show');
