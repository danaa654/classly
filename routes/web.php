<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\UserController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\SpecializationController;

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
| Program Management
|--------------------------------------------------------------------------
*/

Route::resource('programs', ProgramController::class);

/*
|--------------------------------------------------------------------------
| Specialization Management
|--------------------------------------------------------------------------
*/

Route::resource('specializations', SpecializationController::class);

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';