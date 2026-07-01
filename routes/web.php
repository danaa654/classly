<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\SpecializationController;
use App\Http\Controllers\CurriculumController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('welcome');

/*
|--------------------------------------------------------------------------
| Protected Routes (Authentication Required)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard (Role-Based)
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | User Management
    |--------------------------------------------------------------------------
    */

    Route::resource('users', UserController::class);

    /*
    |--------------------------------------------------------------------------
    | Department Management
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
    | Curriculum Management
    |--------------------------------------------------------------------------
    */

    Route::resource('curriculums', CurriculumController::class);

});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

require __DIR__ . '/auth.php';