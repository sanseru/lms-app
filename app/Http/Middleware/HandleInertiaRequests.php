<?php

namespace App\Http\Middleware;

use App\Support\Navigation\NavigationRegistry;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'app' => [
                'name' => config('app.name'),
            ],
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'initials' => $user->initials(),
                    'role' => $user->primaryRole()?->value,
                    'role_label' => $user->primaryRole()?->label(),
                    'roles' => $user->getRoleNames(),
                ] : null,
            ],
            'navigation' => fn () => app(NavigationRegistry::class)->forUser($user),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
