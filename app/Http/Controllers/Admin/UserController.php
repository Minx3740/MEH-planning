<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Direction;
use App\Models\Role;
use App\Models\User;
use App\Services\UserAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $query = User::query()
            ->with(['role', 'direction'])
            ->withCount([
                'sessions as active_sessions_count' => fn ($sessions) => $sessions->where('status', 'ACTIVE'),
            ]);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('firstname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', (int) $request->input('role_id'));
        }

        if ($request->filled('direction_id')) {
            $query->where('direction_id', (int) $request->input('direction_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', strtoupper((string) $request->input('status')));
        }

        $users = $query->orderByDesc('created_at')->paginate(15)->appends($request->query());

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::query()->orderBy('name')->get(),
            'directions' => Direction::query()->orderBy('name')->get(),
        ]);
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        return view('admin.users.show', [
            'user' => $user
                ->load(['role', 'direction'])
                ->loadCount([
                    'sessions as active_sessions_count' => fn ($sessions) => $sessions->where('status', 'ACTIVE'),
                ]),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', [
            'roles' => Role::query()->orderBy('name')->get(),
            'directions' => Direction::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, UserAccountService $userAccountService): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'firstname' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:191', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'direction_id' => ['nullable', 'integer', 'exists:directions,id'],
            'status' => ['nullable', 'string', 'in:ACTIVE,INACTIVE'],
        ]);

        $validated['allow_admin'] = true;

        $userAccountService->create($validated, $request->user());

        return redirect()->route('admin.users.index')->with('status', 'Le compte a bien été créé.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.edit', [
            'user' => $user->load(['role', 'direction']),
            'roles' => Role::query()->orderBy('name')->get(),
            'directions' => Direction::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user, UserAccountService $userAccountService): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'firstname' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:191', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'direction_id' => ['nullable', 'integer', 'exists:directions,id'],
            'password' => ['nullable', 'string', 'confirmed', 'min:8'],
        ]);

        $userAccountService->update($user, $validated, $request->user());

        return redirect()->route('admin.users.show', $user)->with('status', 'Le compte a bien été mis à jour.');
    }

    public function activate(Request $request, User $user, UserAccountService $userAccountService): RedirectResponse
    {
        $this->authorize('activate', $user);

        $userAccountService->activate($user, $request->user());

        return redirect()->route('admin.users.show', $user)->with('status', 'Le compte a été activé.');
    }

    public function deactivate(Request $request, User $user, UserAccountService $userAccountService): RedirectResponse
    {
        $this->authorize('deactivate', $user);

        $userAccountService->deactivate($user, $request->user());

        return redirect()->route('admin.users.show', $user)->with('status', 'Le compte a été désactivé.');
    }

    public function revokeSessions(Request $request, User $user, UserAccountService $userAccountService): RedirectResponse
    {
        $this->authorize('revokeSessions', $user);

        $revokedSessions = $userAccountService->forceLogout($user, $request->user());

        $message = match ($revokedSessions) {
            0 => 'Aucune session active n’a été trouvée pour ce compte.',
            1 => 'Une session active a été révoquée. Le compte reste actif.',
            default => "{$revokedSessions} sessions actives ont été révoquées. Le compte reste actif.",
        };

        return redirect()->route('admin.users.show', $user)->with('status', $message);
    }
}
