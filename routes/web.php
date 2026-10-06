<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GebeurtenisController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/gebeurtenissen', [GebeurtenisController::class, 'index']);