<?php

namespace App\Services;

use App\Models\Direction;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserAccountService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes, ?User $actor = null): User
    {
        $payload = $this->validatePayload($attributes);

        return DB::transaction(function () use ($payload, $actor) {
            $role = Role::query()->find($payload['role_id']);

            if ($role === null) {
                throw ValidationException::withMessages([
                    'role_id' => 'Le rôle est introuvable.',
                ]);
            }

            if (strtoupper((string) $role->code) === Role::ADMIN && ! ($payload['allow_admin'] ?? false)) {
                throw ValidationException::withMessages([
                    'role_id' => 'Le rôle ADMIN ne peut être attribué que par une opération administrative.',
                ]);
            }

            $this->assertRoleAndDirectionConsistency($role, $payload['direction_id'] ?? null);

            if (
                strtoupper((string) $role->code) === Role::DIRECTEUR
                && strtoupper((string) ($payload['status'] ?? 'ACTIVE')) === 'ACTIVE'
            ) {
                app(DirectorAssignmentService::class)->assertAvailable((int) $payload['direction_id']);
            }

            $user = User::query()->create([
                'role_id' => $role->id,
                'direction_id' => $payload['direction_id'] ?? null,
                'name' => $payload['name'],
                'firstname' => $payload['firstname'],
                'email' => strtolower(trim((string) $payload['email'])),
                'password' => $this->normalizePassword((string) $payload['password']),
                'phone' => $payload['phone'] ?? null,
                'status' => strtoupper((string) ($payload['status'] ?? 'ACTIVE')),
            ]);

            app(AccountAuditService::class)->record($actor, 'user.created', $user, [
                'after' => $this->accountSnapshot($user),
            ]);

            return $user;
        });
    }

    public function update(User $user, array $attributes, ?User $actor = null): User
    {
        $payload = $this->validatePayload($attributes, $user);

        return DB::transaction(function () use ($user, $payload, $actor) {
            $user = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $user->load('role');
            $role = Role::query()->find($payload['role_id']);

            if ($role === null) {
                throw ValidationException::withMessages([
                    'role_id' => 'Le rôle est introuvable.',
                ]);
            }

            if ($user->isAdmin() && strtoupper((string) $role->code) !== Role::ADMIN) {
                $remainingAdmins = User::query()
                    ->where('status', 'ACTIVE')
                    ->whereKeyNot($user->id)
                    ->whereHas('role', fn ($query) => $query->where('code', Role::ADMIN))
                    ->count();

                if ($remainingAdmins < 1) {
                    throw ValidationException::withMessages([
                        'role_id' => 'Impossible de retirer le dernier administrateur actif du rôle ADMIN.',
                    ]);
                }
            }

            $this->assertRoleAndDirectionConsistency($role, $payload['direction_id'] ?? null);

            $isActiveDirectorAfterUpdate = strtoupper((string) $role->code) === Role::DIRECTEUR
                && $user->isActive();
            $directorAssignment = app(DirectorAssignmentService::class);
            $directorAssignment->lockDirections([
                $user->isDirector() ? (int) $user->direction_id : null,
                $isActiveDirectorAfterUpdate ? (int) $payload['direction_id'] : null,
            ]);

            if ($isActiveDirectorAfterUpdate) {
                $directorAssignment->assertAvailable((int) $payload['direction_id'], $user->id);
            }

            $before = $this->accountSnapshot($user);
            $user->fill([
                'name' => $payload['name'],
                'firstname' => $payload['firstname'],
                'email' => strtolower(trim((string) $payload['email'])),
                'phone' => $payload['phone'] ?? null,
                'role_id' => $role->id,
                'direction_id' => $payload['direction_id'] ?? null,
            ]);

            if (array_key_exists('password', $payload) && $payload['password'] !== null && trim((string) $payload['password']) !== '') {
                $user->password = $this->normalizePassword((string) $payload['password']);
            }

            $user->save();
            $user->load('role');

            app(AccountAuditService::class)->record($actor, 'user.updated', $user, [
                'before' => $before,
                'after' => $this->accountSnapshot($user),
            ]);

            return $user->fresh();
        });
    }

    public function activate(User $user, ?User $actor = null): User
    {
        return DB::transaction(function () use ($user, $actor) {
            $user = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $user->load('role');

            if ($user->status === 'ACTIVE') {
                return $user;
            }

            if ($user->isDirector()) {
                app(DirectorAssignmentService::class)->assertAvailable((int) $user->direction_id, $user->id);
            }

            $user->status = 'ACTIVE';
            $user->save();

            app(AccountAuditService::class)->record($actor, 'user.activated', $user, [
                'before' => ['status' => 'INACTIVE'],
                'after' => ['status' => 'ACTIVE'],
                'role_id' => $user->role_id,
                'direction_id' => $user->direction_id,
            ]);

            return $user->fresh();
        });
    }

    public function deactivate(User $user, ?User $actor = null): User
    {
        return DB::transaction(function () use ($user, $actor) {
            $user = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $user->load('role');

            if ($user->status !== 'ACTIVE') {
                return $user;
            }

            if ($user->isDirector()) {
                app(DirectorAssignmentService::class)->lockDirections([(int) $user->direction_id]);
            }

            if ($user->isAdmin()) {
                $remainingAdmins = User::query()
                    ->where('status', 'ACTIVE')
                    ->whereKeyNot($user->id)
                    ->whereHas('role', fn ($query) => $query->where('code', Role::ADMIN))
                    ->count();

                if ($remainingAdmins < 1) {
                    throw ValidationException::withMessages([
                        'role_id' => 'Impossible de désactiver le dernier administrateur actif.',
                    ]);
                }
            }

            $user->status = 'INACTIVE';
            $user->save();

            app(UserSessionService::class)->revokeAll($user);
            app(AccountAuditService::class)->record($actor, 'user.deactivated', $user, [
                'before' => ['status' => 'ACTIVE'],
                'after' => ['status' => 'INACTIVE'],
                'role_id' => $user->role_id,
                'direction_id' => $user->direction_id,
                'revoked_sessions' => true,
            ]);

            return $user->fresh();
        });
    }

    public function forceLogout(User $user, ?User $actor = null): int
    {
        return DB::transaction(function () use ($user, $actor) {
            $user = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $revokedSessions = app(UserSessionService::class)->revokeAll($user);

            app(AccountAuditService::class)->record($actor, 'user.sessions_revoked', $user, [
                'revoked_sessions' => $revokedSessions,
                'result' => $revokedSessions > 0 ? 'success' : 'no_active_sessions',
            ]);

            return $revokedSessions;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function validatePayload(array $attributes, ?User $currentUser = null): array
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $firstname = trim((string) ($attributes['firstname'] ?? ''));
        $email = trim((string) ($attributes['email'] ?? ''));
        $password = $attributes['password'] ?? null;
        $roleId = $attributes['role_id'] ?? $attributes['requested_role_id'] ?? null;
        $directionId = $attributes['direction_id'] ?? null;

        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => 'Le nom est obligatoire.',
            ]);
        }

        if ($firstname === '') {
            throw ValidationException::withMessages([
                'firstname' => 'Le prénom est obligatoire.',
            ]);
        }

        if ($email === '') {
            throw ValidationException::withMessages([
                'email' => 'L\'adresse e-mail est obligatoire.',
            ]);
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => 'L\'adresse e-mail est invalide.',
            ]);
        }

        if ($roleId === null || ! is_numeric($roleId)) {
            throw ValidationException::withMessages([
                'role_id' => 'Le rôle est obligatoire.',
            ]);
        }

        $role = Role::query()->find((int) $roleId);

        if ($role === null) {
            throw ValidationException::withMessages([
                'role_id' => 'Le rôle est introuvable.',
            ]);
        }

        $roleCode = strtoupper((string) $role->code);

        if (! in_array($roleCode, [Role::ADMIN, Role::SG, Role::DIRECTEUR], true)) {
            throw ValidationException::withMessages([
                'role_id' => 'Le rôle demandé n\'est pas autorisé.',
            ]);
        }

        if ($roleCode === Role::ADMIN && ! (($attributes['allow_admin'] ?? false) || $currentUser?->isAdmin())) {
            throw ValidationException::withMessages([
                'role_id' => 'Le rôle ADMIN ne peut être attribué que par une opération administrative.',
            ]);
        }

        if ($roleCode !== Role::ADMIN && $directionId !== null && ! is_numeric($directionId)) {
            throw ValidationException::withMessages([
                'direction_id' => 'La direction demandée est invalide.',
            ]);
        }

        $normalizedDirectionId = $directionId !== null && is_numeric($directionId) ? (int) $directionId : null;

        $this->assertRoleAndDirectionConsistency($role, $normalizedDirectionId);

        if ($currentUser !== null && User::query()->whereRaw('LOWER(email) = ?', [strtolower($email)])->whereKeyNot($currentUser->id)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Cette adresse e-mail est déjà utilisée par un autre compte.',
            ]);
        }

        if ($currentUser === null && User::query()->whereRaw('LOWER(email) = ?', [strtolower($email)])->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Cette adresse e-mail est déjà utilisée par un compte existant.',
            ]);
        }

        $payload = [
            'name' => $name,
            'firstname' => $firstname,
            'email' => strtolower($email),
            'role_id' => (int) $roleId,
            'direction_id' => $normalizedDirectionId,
            'phone' => $attributes['phone'] ?? null,
            'status' => strtoupper((string) ($attributes['status'] ?? 'ACTIVE')),
            'allow_admin' => (bool) ($attributes['allow_admin'] ?? false),
        ];

        if ($password !== null && trim((string) $password) !== '') {
            $payload['password'] = (string) $password;
        }

        if ($currentUser === null || array_key_exists('password', $payload)) {
            if (! array_key_exists('password', $payload) || trim((string) $payload['password']) === '') {
                throw ValidationException::withMessages([
                    'password' => 'Le mot de passe est obligatoire.',
                ]);
            }
        }

        return $payload;
    }

    protected function assertRoleAndDirectionConsistency(Role $role, ?int $directionId): void
    {
        $roleCode = strtoupper((string) $role->code);

        if (! in_array($roleCode, [Role::ADMIN, Role::SG, Role::DIRECTEUR], true)) {
            throw ValidationException::withMessages([
                'role_id' => 'Le rôle demandé n\'est pas autorisé.',
            ]);
        }

        if ($roleCode === Role::DIRECTEUR) {
            if ($directionId === null) {
                throw ValidationException::withMessages([
                    'direction_id' => 'Une direction est obligatoire pour un directeur.',
                ]);
            }

            if (Direction::query()->whereKey($directionId)->doesntExist()) {
                throw ValidationException::withMessages([
                    'direction_id' => 'La direction est introuvable.',
                ]);
            }

            return;
        }

        if ($roleCode === Role::SG || $roleCode === Role::ADMIN) {
            return;
        }

        if ($directionId !== null) {
            throw ValidationException::withMessages([
                'direction_id' => 'Ce rôle ne peut pas être associé à une direction.',
            ]);
        }
    }

    /**
     * @return array<string, int|string|null>
     */
    protected function accountSnapshot(User $user): array
    {
        return [
            'name' => $user->name,
            'firstname' => $user->firstname,
            'email' => $user->email,
            'phone' => $user->phone,
            'role_id' => $user->role_id,
            'role_code' => $user->role?->code,
            'direction_id' => $user->direction_id,
            'status' => $user->status,
        ];
    }

    protected function normalizePassword(string $password): string
    {
        $passwordInfo = password_get_info($password);

        if (is_array($passwordInfo) && ($passwordInfo['algo'] ?? null) !== null && ($passwordInfo['algo'] ?? false) !== false) {
            return $password;
        }

        return Hash::make($password);
    }
}
