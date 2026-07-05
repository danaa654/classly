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
use App\Http\Controllers\CurriculumItemController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\SubjectOfferingController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\AcademicTermController;
use App\Http\Controllers\TeachingAssignmentController;
use App\Http\Controllers\MasterGridController;
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
        | Subject Offerings — Generate / Delete (Admin + Registrar only)
        |--------------------------------------------------------------------------
        |
        | Viewing the list (index) is registered further down, in the
        | Admin|Registrar|Dean|Assistant Dean|OIC group — Dean/Assistant
        | Dean/OIC need to see what's been offered to make sense of
        | Faculty Loading, but they never generate or delete an
        | offering. Generating/deleting stay here, Admin + Registrar
        | only, and are additionally checked per-action in
        | SubjectOfferingController (SubjectOfferingPolicy::generate()
        | for create/store, an explicit role check for destroy) so a
        | direct hit still 403s even if this route grouping is ever
        | rearranged later.
        |
        | No resource route here on purpose — there is no update for a
        | single offering (Overall Status is fully derived, not
        | editable). "create"/"store" are the Generate form, not a
        | manual record form. Faculty assignment for an offering
        | happens in Faculty Loading (Teaching Assignments), not here.
        */

        Route::get('subject-offerings/create', [SubjectOfferingController::class, 'create'])
            ->name('subject-offerings.create');

        Route::post('subject-offerings', [SubjectOfferingController::class, 'store'])
            ->name('subject-offerings.store');

        Route::delete('subject-offerings/{subjectOffering}', [SubjectOfferingController::class, 'destroy'])
            ->name('subject-offerings.destroy');

    });

    /*
    |--------------------------------------------------------------------------
    | ADMIN + REGISTRAR + DEAN + ASSISTANT DEAN + OIC
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:Admin|Registrar|Dean|Assistant Dean|OIC')->group(function () {

        Route::resource('faculty', FacultyController::class);

        /*
        |--------------------------------------------------------------------------
        | Faculty Preferences ("Manage Subjects")
        |--------------------------------------------------------------------------
        |
        | Per-faculty workspace for selecting which active-term Subject
        | Offerings a faculty member PREFERS to teach. This stores
        | preferences only (see faculty_subject_offering) — no
        | day/time/room is assigned here, and this is NOT the actual
        | Faculty Loading assignment (that's Teaching Assignments,
        | above). Direct mirror of Rooms' "Manage Subjects" below.
        |
        */

        Route::get('faculty/{faculty}/manage-subjects', [FacultyController::class, 'manageSubjects'])
            ->name('faculty.manage-subjects');

        Route::put('faculty/{faculty}/manage-subjects', [FacultyController::class, 'syncPreferredSubjects'])
            ->name('faculty.manage-subjects.update');

        Route::resource('subjects', SubjectController::class);

        // Rooms — master list only (no schedules/availability here).
        Route::resource('rooms', RoomController::class);

        /*
        |--------------------------------------------------------------------------
        | Room Preferences ("Manage Subjects")
        |--------------------------------------------------------------------------
        |
        | Per-room workspace for selecting which active-term Subject
        | Offerings a room PREFERS to host. This stores preferences only
        | (see room_subject_offering) — no day/time/faculty is assigned
        | here. That belongs to the future Scheduling module.
        |
        */

        Route::get('rooms/{room}/manage-subjects', [RoomController::class, 'manageSubjects'])
            ->name('rooms.manage-subjects');

        Route::put('rooms/{room}/manage-subjects', [RoomController::class, 'syncPreferredSubjects'])
            ->name('rooms.manage-subjects.update');

        /*
        |--------------------------------------------------------------------------
        | Subject Offerings — View only
        |--------------------------------------------------------------------------
        |
        | Dean/Assistant Dean/OIC can see what classes exist for the
        | term (they need this to make sense of Faculty Loading below),
        | but never generate or delete an offering — those actions live
        | in the Admin|Registrar group above, out of reach here even by
        | a direct route hit.
        */

        Route::get('subject-offerings', [SubjectOfferingController::class, 'index'])
            ->name('subject-offerings.index');

        /*
        |--------------------------------------------------------------------------
        | Subject Offerings — Printable Class List
        |--------------------------------------------------------------------------
        |
        | Same viewers as the Index above — a partial class list for
        | posting before enrollment. No Faculty/Room/Time is shown or
        | required, so this is safe for the same role group that can
        | already see the Index.
        */

        Route::get('subject-offerings/print', [SubjectOfferingController::class, 'print'])
            ->name('subject-offerings.print');

        /*
        |--------------------------------------------------------------------------
        | Faculty Loading (Teaching Assignments)
        |--------------------------------------------------------------------------
        |
        | Assigns which faculty member teaches each Subject Offering
        | for the active academic term. This is NOT the final room/time
        | schedule — Subject Offerings stay unscheduled here; the
        | Greedy Scheduler (Master Grid) is what assigns room and time
        | slots, checking conflicts at that stage.
        |
        | Only index/store/destroy are registered — this module has no
        | standalone create/edit pages. Assigning happens via the
        | "Assign Subject" modal on the index page (POST straight to
        | store); removing a load is a destroy from the same page.
        | Faculty Subject "qualification" no longer exists anywhere in
        | this system — eligibility is decided purely by Faculty Scope
        | + Department + Subject Category (see
        | TeachingAssignmentService).
        |
        */

        Route::resource('teaching-assignments', TeachingAssignmentController::class)
            ->only(['index', 'store', 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | Master Grid Scheduling Workspace
        |--------------------------------------------------------------------------
        |
        | Read-only visual workspace for the active Academic Term —
        | timetable + Subject/Room sidebars. index() is viewable by
        | everyone in this role group (Admin/Registrar/Dean/Assistant
        | Dean/OIC all have a stake in seeing the scheduling
        | workspace).
        |
        */

        Route::get('master-grid', [MasterGridController::class, 'index'])
            ->name('master-grid.index');

        /*
        |--------------------------------------------------------------------------
        | Master Grid — Generate Schedule (Greedy Scheduling Algorithm)
        |--------------------------------------------------------------------------
        |
        | Preview-only endpoint: runs GreedyScheduleService for one
        | Department + Program + [Specialization] + Year Level +
        | Section and returns a draft schedule as JSON. It does NOT
        | write to the database — see GreedyScheduleService's docblock.
        | Restricted to Admin/Registrar only, unlike index() above,
        | per spec ("When the Registrar or Admin clicks Generate
        | Schedule..."). MasterGridController::middleware() also
        | double-checks this same restriction on the controller side,
        | so a direct hit still 403s even if this route grouping is
        | ever rearranged later.
        |
        */

        Route::middleware('role:Admin|Registrar')->group(function () {

            Route::post('master-grid/generate', [MasterGridController::class, 'generate'])
                ->name('master-grid.generate');

            // Interactive Schedule Review (Phase 2): validate-block runs
            // on every field change inside the Edit Schedule modal; save
            // re-validates the whole preview and, only if clean, inserts
            // every block into `schedules` in one transaction. Both act
            // purely on the in-memory preview the client already holds.
            Route::post('master-grid/validate-block', [MasterGridController::class, 'validateBlock'])
                ->name('master-grid.validate-block');

            Route::post('master-grid/save', [MasterGridController::class, 'save'])
                ->name('master-grid.save');

        });

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