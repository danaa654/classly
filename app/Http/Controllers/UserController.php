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
    public function edit(User $user)
{
    $user->load('roles');

    return Inertia::render('Users/Edit', [
        'user' => $user,
        'roles' => Role::all(),
        'departments' => Department::all(),
    ]);
}

    /**
     * Update the user.
     */
    public function update(Request $request, User $user)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,' . $user->id,
        'password' => 'nullable|min:6',
        'role' => 'required|exists:roles,name',
        'department_id' => 'nullable|exists:departments,id',
    ]);

    // Roles that don't need a department
    if (in_array($validated['role'], ['Admin', 'Registrar', 'Assistant Dean'])) {
        $validated['department_id'] = null;
    }

    $user->name = $validated['name'];
    $user->email = $validated['email'];
    $user->department_id = $validated['department_id'];

    if (!empty($validated['password'])) {
        $user->password = bcrypt($validated['password']);
    }

    $user->save();

    // Update role
    $user->syncRoles([$validated['role']]);

    return redirect()->route('users.index')
        ->with('success', 'User updated successfully.');
}
    /**
     * Delete the user.
     */
    public function destroy(User $user)
{
    // Prevent deleting yourself
    if (auth()->id() === $user->id) {
        return redirect()->back()
            ->with('error', 'You cannot delete your own account.');
    }

    $user->delete();

    return redirect()->route('users.index')
        ->with('success', 'User deleted successfully.');
}
}