<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Admin
        if ($user->hasRole('Admin')) {
            return Inertia::render('Dashboard/Admin');
        }

        // Registrar
        if ($user->hasRole('Registrar')) {
            return Inertia::render('Dashboard/Registrar');
        }

        // Dean, Assistant Dean, and OIC share one dashboard
        if (
            $user->hasRole('Dean') ||
            $user->hasRole('Assistant Dean') ||
            $user->hasRole('OIC')
        ) {
            return Inertia::render('Dashboard/Dean');
        }

        // Default
        return Inertia::render('Dashboard/Index');
    }
}