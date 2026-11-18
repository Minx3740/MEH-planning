<?php

namespace App\Http\Controllers;

use App\Models\Direction;
use App\Models\Role;
use App\Services\AccountRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountRequestController extends Controller
{
    public function create(): View
    {
        $roles = Role::query()
            ->whereIn('code', [Role::SG, Role::DIRECTEUR])
            ->orderBy('name')
            ->get();
        $directions = Direction::query()
            ->with('group')
            ->orderBy('name')
            ->get();

        return view('account-requests.create', [
            'roles' => $roles,
            'directorRoleId' => $roles->firstWhere('code', Role::DIRECTEUR)?->id,
            'directions' => $directions,
        ]);
    }

    public function store(Request $request, AccountRequestService $service): RedirectResponse
    {
        $validated = $request->validate(
            [
                'name' => ['required', 'string', 'max:100'],
                'firstname' => ['required', 'string', 'max:100'],
                'email' => ['required', 'email', 'max:191'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'phone' => ['nullable', 'string', 'max:30'],
                'requested_role_id' => ['required', 'integer', 'exists:roles,id'],
                'direction_id' => ['nullable', 'integer', 'exists:directions,id'],
            ],
            [
                'name.required' => 'Le nom est obligatoire.',
                'firstname.required' => 'Le prénom est obligatoire.',
                'email.required' => "L'adresse e-mail est obligatoire.",
                'email.email' => "L'adresse e-mail doit être valide.",
                'password.required' => 'Le mot de passe est obligatoire.',
                'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
                'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
                'requested_role_id.required' => 'Le rôle demandé est obligatoire.',
                'requested_role_id.exists' => 'Le rôle sélectionné est invalide.',
                'direction_id.integer' => 'La direction sélectionnée est invalide.',
                'direction_id.exists' => "La direction sélectionnée n'existe pas.",
            ],
        );

        $role = Role::query()->findOrFail((int) $validated['requested_role_id']);

        if (strtoupper((string) $role->code) === Role::ADMIN) {
            abort(422, 'Le rôle ADMIN ne peut pas être demandé publiquement.');
        }

        if (strtoupper((string) $role->code) === Role::DIRECTEUR) {
            if (Direction::query()->doesntExist()) {
                throw ValidationException::withMessages([
                    'direction_id' => ["Aucune direction n'est actuellement disponible pour les demandes de DIRECTEUR."],
                ]);
            }

            if (empty($validated['direction_id'])) {
                throw ValidationException::withMessages([
                    'direction_id' => ['Une direction est obligatoire pour demander le rôle DIRECTEUR.'],
                ]);
            }
        }

        $service->createRequest($validated);

        return redirect()->route('account-requests.create')->with('status', 'Votre demande a bien été soumise et est en attente de validation.');
    }
}
