<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\UserController;

Route::get('/', function () {
    return Inertia::render('Dashboard/Index');
});

Route::get('/users', [UserController::class, 'index']);

require __DIR__.'/auth.php';