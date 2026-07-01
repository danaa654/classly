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
| Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | ADMIN ONLY
    |--------------------------------------------------------------------------
    | Only the Admin can create/manage user accounts.
    */

    Route::middleware('role:Admin')->group(function () {

        Route::resource('users', UserController::class);

    });

    /*
    |--------------------------------------------------------------------------
    | ADMIN + REGISTRAR
    |--------------------------------------------------------------------------
    | Registrar has the same power as Admin except Users.
    */

    Route::middleware('role:Admin|Registrar')->group(function () {

        Route::resource('departments', DepartmentController::class);

        Route::resource('programs', ProgramController::class);

        Route::resource('specializations', SpecializationController::class);

        Route::resource('curriculums', CurriculumController::class);

    });

    /*
    |--------------------------------------------------------------------------
    | ADMIN + REGISTRAR + DEAN + ASSISTANT DEAN + OIC
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:Admin|Registrar|Dean|Assistant Dean|OIC')->group(function () {

        Route::resource('faculty', FacultyController::class);

        // Future Modules
        // Route::resource('subjects', SubjectController::class);
        // Route::resource('rooms', RoomController::class);
        // Route::resource('schedules', ScheduleController::class);

    });

});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';