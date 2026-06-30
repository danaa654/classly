<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Department;
use Spatie\Permission\Models\Role;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index()
{
    $users = User::with('roles', 'department')->get();

    $users->each(function ($user) {

        if ($user->hasRole(['Admin', 'Registrar', 'Assistant Dean'])) {
            $user->department_name = 'All Departments';
        } else {
            $user->department_name = optional($user->department)->short_name;
        }

    });

    return Inertia::render('Users/Index', [
        'users' => $users,
    ]);
}
    /**
     * Show the create user page.
     */
    public function create()
    {
        return Inertia::render('Users/Create', [
            'roles' => Role::all(),
            'departments' => Department::all(),
        ]);
    }

    /**
     * Store a new user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:6'],
            'role' => ['required', 'exists:roles,name'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);

        // Roles that don't need a department
        $rolesWithoutDepartment = [
            'Admin',
            'Registrar',
            'Assistant Dean',
        ];

        if (in_array($validated['role'], $rolesWithoutDepartment)) {
            $validated['department_id'] = null;
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'department_id' => $validated['department_id'],
        ]);

        $user->assignRole($validated['role']);

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the edit form.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the user.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Delete the user.
     */
    public function destroy(string $id)
    {
        //
    }
}