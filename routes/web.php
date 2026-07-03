<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\FacultySubjectController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\SpecializationController;
use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\CurriculumItemController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\SubjectOfferingController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\AcademicTermController;
use App\Http\Controllers\TeachingAssignmentController;
use App\Http\Controllers\ProfileController;
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

        /*
        |--------------------------------------------------------------------------
        | Curriculum Items
        |--------------------------------------------------------------------------
        |
        | Standard CRUD for attaching items (Subject or OJT) into a
        | curriculum's prospectus. ->parameters() keeps the route
        | wildcard camelCase ({curriculumItem}) so it matches the
        | controller's $curriculumItem argument for implicit model
        | binding.
        |
        */

        Route::resource('curriculum-items', CurriculumItemController::class)
            ->except(['show'])
            ->parameters(['curriculum-items' => 'curriculumItem']);

        // Curriculum-scoped "Manage Items" workspace — grouped by year
        // level and semester for a single curriculum.
        Route::get('/curriculums/{curriculum}/items', [CurriculumItemController::class, 'manage'])
            ->name('curriculums.items.manage');

        /*
        |--------------------------------------------------------------------------
        | Sections
        |--------------------------------------------------------------------------
        |
        | Section master data (e.g. BSIT-1A) — grouped by curriculum. No
        | scheduling logic lives here; this is used later by the
        | automatic scheduling engine.
        |
        */

        Route::resource('sections', SectionController::class);

        /*
        |--------------------------------------------------------------------------
        | Academic Terms
        |--------------------------------------------------------------------------
        |
        | One Academic Term = one scheduling period (e.g. AY 2026-2027,
        | 1st Semester). The registrar can prepare future terms while
        | the current term is still active — only one term may have
        | active = true at any time (enforced in the controller).
        |
        */

        Route::resource('academic-terms', AcademicTermController::class);

        /*
        |--------------------------------------------------------------------------
        | Subject Offerings
        |--------------------------------------------------------------------------
        |
        | The actual classes offered for a selected Academic Term —
        | generated (never manually created) from active Sections +
        | their Curriculum's Curriculum Items. Admin + Registrar only:
        | Dean/Assistant Dean/OIC have no access to this module at all
        | (no Sidebar link, and a direct hit on any of these routes
        | 403s via SubjectOfferingController::middleware() /
        | SubjectOfferingPolicy). "generate" (Create/Store below) is
        | additionally checked per-action against
        | SubjectOfferingPolicy::generate() — currently the same
        | Admin|Registrar set, kept separate so it can be narrowed on
        | its own later without touching view access.
        |
        | No resource route here on purpose — there is no store/update/
        | destroy for a single offering. "create"/"store" below are the
        | Generate form, not a manual record form.
        |
        */

        Route::get('subject-offerings', [SubjectOfferingController::class, 'index'])
            ->name('subject-offerings.index');

        Route::get('subject-offerings/generate', [SubjectOfferingController::class, 'create'])
            ->name('subject-offerings.create');

        Route::post('subject-offerings/generate', [SubjectOfferingController::class, 'store'])
            ->name('subject-offerings.store');

    });

    /*
    |--------------------------------------------------------------------------
    | ADMIN + REGISTRAR + DEAN + ASSISTANT DEAN + OIC
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:Admin|Registrar|Dean|Assistant Dean|OIC')->group(function () {

        Route::resource('faculty', FacultyController::class);
        Route::resource('subjects', SubjectController::class);

        // Faculty Subject Assignment — which subjects a faculty member is
        // allowed to teach.
        Route::resource('faculty-subjects', FacultySubjectController::class)
            ->except(['show']);

        // Rooms — master list only (no schedules/availability here).
        Route::resource('rooms', RoomController::class);

        /*
        |--------------------------------------------------------------------------
        | Teaching Assignments (Faculty Loading)
        |--------------------------------------------------------------------------
        |
        | Assigns which faculty member teaches each curriculum item, for
        | which section, in a given academic term. This is NOT the final
        | room/time schedule — it only prepares the data the future
        | Greedy Scheduler will consume.
        |
        */

        Route::resource('teaching-assignments', TeachingAssignmentController::class);

        // Future Modules
        // Route::resource('schedules', ScheduleController::class);

    });

});

/*
|--------------------------------------------------------------------------
| Profile
|--------------------------------------------------------------------------
|
| Every authenticated user (regardless of role) can manage their own
| account — these were missing from the route file, which caused Ziggy
| to throw on any page rendering AuthenticatedLayout.vue (it always
| calls route('profile.edit') and route('logout') in the nav dropdown).
|
*/

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';