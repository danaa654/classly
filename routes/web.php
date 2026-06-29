<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\UserController;

Route::get('/', function () {
    return Inertia::render('Dashboard/Index');
});

Route::resource('users', UserController::class);

require __DIR__.'/auth.php';