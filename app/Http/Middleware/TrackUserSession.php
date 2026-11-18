<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\UserSessionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackUserSession
{
    public function __construct(
        private UserSessionService $userSessionService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('logout') || $request->routeIs('login') || $request->routeIs('login.store')) {
            return $next($request);
        }

        $guard = Auth::guard('web');
        $user = $guard->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        $userSession = $this->userSessionService->findForUser(
            $user,
            $request->session()->getId()
        );

        if ($userSession === null || ! $user->isActive()) {
            $guard->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(
                401,
                'Votre session a expiré, a été révoquée ou votre compte est désactivé. Veuillez vous reconnecter.'
            );
        }

        $this->userSessionService->updateActivity($userSession);

        return $next($request);
    }
}
