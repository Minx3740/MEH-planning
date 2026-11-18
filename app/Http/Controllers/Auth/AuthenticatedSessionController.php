<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\UserSession;
use App\Services\UserSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(private UserSessionService $userSessionService) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Les identifiants fournis sont incorrects.',
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::guard('web')->user();

        if ($user === null || $user->status !== 'ACTIVE') {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Votre compte est désactivé ou indisponible.',
            ]);
        }

        $user->update([
            'last_login' => now(),
        ]);

        $this->userSessionService->create($user, $request);

        return redirect()->route('dashboard');
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $sessions = $this->userSessionService->sessionsForUser($user);

        return view('auth.sessions', [
            'sessions' => $sessions,
            'currentSessionHash' => hash('sha256', $request->session()->getId()),
        ]);
    }

    public function revoke(Request $request, UserSession $userSession): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user !== null && $userSession->user_id === $user->id, 403);

        $this->userSessionService->revoke($userSession);

        $currentSessionHash = hash('sha256', $request->session()->getId());

        if ($userSession->session_key_hash === $currentSessionHash) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        return redirect()->route('sessions.index');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::guard('web')->user();

        if ($user !== null) {
            $userSession = $this->userSessionService->findForUser($user, $request->session()->getId());

            if ($userSession !== null) {
                $this->userSessionService->revoke($userSession);
            }
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(route('login'));
    }
}
