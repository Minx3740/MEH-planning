<?php

namespace App\Http\Middleware;

use App\Models\Direction;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDirectionAccess
{
    public function handle(Request $request, Closure $next, string $ability = 'view'): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $direction = $this->resolveDirection($request);

        if ($direction === null) {
            abort(403, 'La direction demandée est introuvable ou non autorisée.');
        }

        $authorized = $ability === 'manage'
            ? $user->canManageDirection($direction)
            : $user->canAccessDirection($direction);

        if (! $authorized) {
            abort(403, 'Vous n\'avez pas accès à cette direction.');
        }

        return $next($request);
    }

    protected function resolveDirection(Request $request): mixed
    {
        $route = $request->route();

        if ($route === null) {
            $directionId = $request->input('direction_id');

            return $directionId !== null ? $directionId : null;
        }

        foreach (['direction', 'direction_id', 'activity', 'resource'] as $parameterName) {
            $value = $route->parameter($parameterName);

            if ($value instanceof Direction) {
                return $value;
            }

            if ($value !== null && is_object($value) && isset($value->direction_id)) {
                return $value->direction_id;
            }

            if ($value !== null && is_numeric($value)) {
                return (int) $value;
            }

            if ($value !== null && is_string($value) && ctype_digit(trim($value))) {
                return (int) trim($value);
            }
        }

        $directionId = $request->input('direction_id');

        return is_numeric($directionId) ? (int) $directionId : null;
    }
}
