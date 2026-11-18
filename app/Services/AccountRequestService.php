<?php

namespace App\Services;

use App\Models\AccountRequest;
use App\Models\Direction;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AccountRequestService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createRequest(array $attributes): AccountRequest
    {
        $payload = $this->validatePayload($attributes);

        return DB::transaction(function () use ($payload) {
            $role = Role::query()
                ->whereKey($payload['requested_role_id'])
                ->lockForUpdate()
                ->first();

            if ($role === null || ! in_array(strtoupper((string) $role->code), [Role::SG, Role::DIRECTEUR], true)) {
                throw ValidationException::withMessages([
                    'requested_role_id' => 'Le rôle demandé n\'est pas autorisé.',
                ]);
            }

            $this->ensureRequestedDirectionIsValid($payload['direction_id'] ?? null, (int) $role->id);
            $this->ensureNoDuplicatePendingRequest($payload);

            if (User::query()->whereRaw('LOWER(email) = ?', [strtolower($payload['email'])])->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'Cette adresse e-mail est déjà utilisée par un compte existant.',
                ]);
            }

            $accountRequest = AccountRequest::query()->create([
                'direction_id' => $payload['direction_id'] ?? null,
                'requested_role_id' => $role->id,
                'name' => $payload['name'],
                'firstname' => $payload['firstname'],
                'email' => strtolower(trim($payload['email'])),
                'password' => $this->normalizePassword($payload['password']),
                'phone' => $payload['phone'] ?? null,
                'status' => 'PENDING',
                'created_user_id' => null,
            ]);

            app(AccountAuditService::class)->record(null, 'account_request.submitted', $accountRequest, [
                'name' => $accountRequest->name,
                'firstname' => $accountRequest->firstname,
                'email' => $accountRequest->email,
                'requested_role_id' => $accountRequest->requested_role_id,
                'direction_id' => $accountRequest->direction_id,
            ]);

            return $accountRequest;
        });
    }

    public function approve(AccountRequest $request, User $reviewer): AccountRequest
    {
        return DB::transaction(function () use ($request, $reviewer) {
            $request = AccountRequest::query()
                ->whereKey($request->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($request->status !== 'PENDING') {
                throw ValidationException::withMessages([
                    'status' => 'Cette demande n\'est plus en attente de traitement.',
                ]);
            }

            $role = $request->requestedRole()->first();

            if ($role === null || ! in_array(strtoupper((string) $role->code), [Role::SG, Role::DIRECTEUR], true)) {
                throw ValidationException::withMessages([
                    'requested_role_id' => 'Le rôle demandé est invalide.',
                ]);
            }

            $email = strtolower(trim((string) $request->email));

            if (User::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'Cette adresse e-mail est déjà utilisée par un compte existant.',
                ]);
            }

            $directionId = $request->direction_id !== null ? (int) $request->direction_id : null;

            if (strtoupper((string) $role->code) === Role::DIRECTEUR) {
                if ($directionId === null) {
                    throw ValidationException::withMessages([
                        'direction_id' => 'Une direction est obligatoire pour un directeur.',
                    ]);
                }

                app(DirectorAssignmentService::class)->assertAvailable($directionId);
            }

            $user = User::query()->create([
                'role_id' => $role->id,
                'direction_id' => $directionId,
                'name' => $request->name,
                'firstname' => $request->firstname,
                'email' => $email,
                'password' => $this->normalizePassword((string) $request->password),
                'phone' => $request->phone,
                'status' => 'ACTIVE',
            ]);

            $updated = AccountRequest::query()
                ->whereKey($request->getKey())
                ->where('status', 'PENDING')
                ->update([
                    'status' => 'APPROVED',
                    'reviewed_by' => $reviewer->id,
                    'reviewed_at' => now(),
                    'created_user_id' => $user->id,
                    'decision_reason' => $request->decision_reason ?? null,
                ]);

            if ($updated !== 1) {
                throw ValidationException::withMessages([
                    'status' => 'Cette demande a déjà été traitée.',
                ]);
            }

            $audit = app(AccountAuditService::class);
            $audit->record($reviewer, 'user.created_from_account_request', $user, [
                'account_request_id' => $request->id,
                'role_id' => $user->role_id,
                'role_code' => $role->code,
                'direction_id' => $user->direction_id,
                'status' => $user->status,
            ]);
            $audit->record($reviewer, 'account_request.approved', $request, [
                'created_user_id' => $user->id,
                'requested_role_id' => $request->requested_role_id,
                'direction_id' => $request->direction_id,
            ]);

            return $request->fresh();
        });
    }

    public function reject(AccountRequest $request, User $reviewer, ?string $reason = null): AccountRequest
    {
        return DB::transaction(function () use ($request, $reviewer, $reason) {
            $request = AccountRequest::query()
                ->whereKey($request->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($request->status !== 'PENDING') {
                throw ValidationException::withMessages([
                    'status' => 'Une demande déjà traitée ne peut pas être refusée.',
                ]);
            }

            $updated = AccountRequest::query()
                ->whereKey($request->getKey())
                ->where('status', 'PENDING')
                ->update([
                    'status' => 'REJECTED',
                    'reviewed_by' => $reviewer->id,
                    'reviewed_at' => now(),
                    'decision_reason' => $reason,
                ]);

            if ($updated !== 1) {
                throw ValidationException::withMessages([
                    'status' => 'Une demande déjà traitée ne peut pas être refusée.',
                ]);
            }

            app(AccountAuditService::class)->record($reviewer, 'account_request.rejected', $request, [
                'requested_role_id' => $request->requested_role_id,
                'direction_id' => $request->direction_id,
                'reason' => $reason,
            ]);

            return $request->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function validatePayload(array $attributes): array
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $firstname = trim((string) ($attributes['firstname'] ?? ''));
        $email = trim((string) ($attributes['email'] ?? ''));
        $password = (string) ($attributes['password'] ?? '');
        $directionId = $attributes['direction_id'] ?? null;
        $requestedRoleId = $attributes['requested_role_id'] ?? null;

        if ($name === '' || $firstname === '' || $email === '' || $password === '') {
            throw ValidationException::withMessages([
                'name' => 'Le nom est obligatoire.',
                'firstname' => 'Le prénom est obligatoire.',
                'email' => 'L\'adresse e-mail est obligatoire.',
                'password' => 'Le mot de passe est obligatoire.',
            ]);
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => 'L\'adresse e-mail est invalide.',
            ]);
        }

        $normalizedDirectionId = $directionId !== null && is_numeric($directionId) ? (int) $directionId : null;
        $requestedRole = Role::query()->find($requestedRoleId);

        if ($requestedRole === null) {
            throw ValidationException::withMessages([
                'requested_role_id' => 'Le rôle demandé est introuvable.',
            ]);
        }

        if (strtoupper((string) $requestedRole->code) === Role::ADMIN) {
            throw ValidationException::withMessages([
                'requested_role_id' => 'Le rôle ADMIN ne peut pas être demandé publiquement.',
            ]);
        }

        if (! in_array(strtoupper((string) $requestedRole->code), [Role::SG, Role::DIRECTEUR], true)) {
            throw ValidationException::withMessages([
                'requested_role_id' => 'Seuls les rôles SG et DIRECTEUR peuvent être demandés.',
            ]);
        }

        if (strtoupper((string) $requestedRole->code) === Role::DIRECTEUR) {
            if ($normalizedDirectionId === null) {
                throw ValidationException::withMessages([
                    'direction_id' => 'Une direction est obligatoire pour un directeur.',
                ]);
            }

            $direction = Direction::query()->find($normalizedDirectionId);

            if ($direction === null) {
                throw ValidationException::withMessages([
                    'direction_id' => 'La direction demandée est introuvable.',
                ]);
            }
        }

        if (User::query()->whereRaw('LOWER(email) = ?', [strtolower($email)])->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Cette adresse e-mail est déjà utilisée par un compte existant.',
            ]);
        }

        return [
            'name' => $name,
            'firstname' => $firstname,
            'email' => $email,
            'password' => $password,
            'phone' => $attributes['phone'] ?? null,
            'direction_id' => $normalizedDirectionId,
            'requested_role_id' => $requestedRole->id,
            'created_user_id' => $attributes['created_user_id'] ?? null,
        ];
    }

    protected function ensureRoleAllowed(int $requestedRoleId): void
    {
        $role = Role::query()->find($requestedRoleId);

        if ($role === null || ! in_array(strtoupper((string) $role->code), [Role::SG, Role::DIRECTEUR], true)) {
            throw ValidationException::withMessages([
                'requested_role_id' => 'Le rôle demandé n\'est pas autorisé.',
            ]);
        }
    }

    protected function ensureRequestedDirectionIsValid(?int $directionId, int $requestedRoleId): void
    {
        $role = Role::query()->find($requestedRoleId);

        if ($role === null) {
            return;
        }

        if (strtoupper((string) $role->code) !== Role::DIRECTEUR) {
            return;
        }

        if ($directionId === null) {
            throw ValidationException::withMessages([
                'direction_id' => 'Une direction est obligatoire pour le rôle directeur.',
            ]);
        }

        if (Direction::query()->whereKey($directionId)->doesntExist()) {
            throw ValidationException::withMessages([
                'direction_id' => 'La direction demandée est introuvable.',
            ]);
        }
    }

    protected function ensureNoDuplicatePendingRequest(array $payload): void
    {
        $query = AccountRequest::query()
            ->where('status', 'PENDING')
            ->whereRaw('LOWER(email) = ?', [strtolower(trim((string) $payload['email']))]);

        if (array_key_exists('requested_role_id', $payload) && $payload['requested_role_id'] !== null) {
            $query->where('requested_role_id', $payload['requested_role_id']);
        }

        if (array_key_exists('direction_id', $payload) && $payload['direction_id'] !== null) {
            $query->where('direction_id', $payload['direction_id']);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Une demande identique est déjà en attente de validation.',
            ]);
        }
    }

    protected function normalizePassword(string $password): string
    {
        if (trim($password) === '') {
            throw ValidationException::withMessages([
                'password' => 'Le mot de passe est obligatoire.',
            ]);
        }

        $passwordInfo = password_get_info($password);

        if (is_array($passwordInfo) && ($passwordInfo['algo'] ?? null) !== null && ($passwordInfo['algo'] ?? false) !== false) {
            return $password;
        }

        return Hash::make($password);
    }
}
