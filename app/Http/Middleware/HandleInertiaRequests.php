<?php

namespace App\Http\Middleware;

use App\Models\AcademicTerm;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),

            // Shared on every Inertia response so any page/component can read
            // $page.props.activeAcademicTerm without the controller having to
            // fetch or pass it. Resolves to null (never throws) when no term
            // is currently marked active.
            'activeAcademicTerm' => fn () => AcademicTerm::active()->first(),

            'auth' => [
                'user' => $user
                    ? [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,

                        // Array of role names
                        'roles' => $user->getRoleNames()->toArray(),

                        // Array of permission names
                        'permissions' => $user->getPermissionNames()->toArray(),
                    ]
                    : null,
            ],
        ];
    }
}