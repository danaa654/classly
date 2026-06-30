<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\UserController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\FacultyController;

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Dashboard/Index');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard/Index');
})->name('dashboard');

/*
|--------------------------------------------------------------------------
| User Management
|--------------------------------------------------------------------------
*/

Route::resource('users', UserController::class);

/*
|--------------------------------------------------------------------------
| College Management
|--------------------------------------------------------------------------
*/

Route::resource('departments', DepartmentController::class);

/*
|--------------------------------------------------------------------------
| Faculty Management
|--------------------------------------------------------------------------
*/

Route::resource('faculty', FacultyController::class);

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';