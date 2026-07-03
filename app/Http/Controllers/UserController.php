<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, $next) {

                abort_unless(
                    auth()->user()->hasRole('Admin'),
                    403,
                    'Unauthorized.'
                );

                return $next($request);
            }),
        ];
    }

    /**
     * Display a listing of users.
     */
    public function index()
    {
        $users = User::with(['roles', 'department'])
            ->orderBy('name')
            ->get();

        $users->each(function ($user) {

            $user->department_name = $user->hasRole([
                'Admin',
                'Registrar',
                'Assistant Dean',
            ])
                ? 'All Departments'
                : optional($user->department)->abbreviation;

        });

        return Inertia::render('Users/Index', [
            'users' => $users,
        ]);
    }

    /**
     * Show the create form.
     */
    public function create()
    {
        return Inertia::render('Users/Create', [

            'roles' => Role::orderBy('name')->get(),

            'departments' => Department::where('active', true)
                ->orderBy('abbreviation')
                ->get(),

        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'unique:users,email'],
            'password'      => ['required', 'min:6', 'confirmed'],
            'role'          => ['required', 'exists:roles,name'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);

        $rolesWithoutDepartment = [
            'Admin',
            'Registrar',
            'Assistant Dean',
        ];

        if (in_array($validated['role'], $rolesWithoutDepartment)) {
            $validated['department_id'] = null;
        }

        $user = User::create([
            'name'          => $validated['name'],
            'email'         => $validated['email'],
            'password'      => bcrypt($validated['password']),
            'department_id' => $validated['department_id'],
        ]);

        $user->assignRole($validated['role']);

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Show the edit form.
     */
    public function edit(User $user)
    {
        $user->load('roles');

        return Inertia::render('Users/Edit', [

            'user' => $user,

            'roles' => Role::orderBy('name')->get(),

            'departments' => Department::where('active', true)
                ->orderBy('abbreviation')
                ->get(),

        ]);
    }

    /**
     * Update the user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $user->id,
            'password'      => 'nullable|min:6|confirmed',
            'role'          => 'required|exists:roles,name',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        if (in_array($validated['role'], [
            'Admin',
            'Registrar',
            'Assistant Dean',
        ])) {
            $validated['department_id'] = null;
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->department_id = $validated['department_id'];

        if (!empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }

        $user->save();

        $user->syncRoles([$validated['role']]);

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Delete the user.
     */
    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with(
                'error',
                'You cannot delete your own account.'
            );
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}