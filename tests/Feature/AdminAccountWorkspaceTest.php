<?php

namespace Tests\Feature;

use App\Http\Middleware\TrackUserSession;
use App\Models\AccountRequest;
use App\Models\AuditLog;
use App\Models\Direction;
use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class AdminAccountWorkspaceTest extends TestCase
{
    public function test_admin_can_filter_requests_and_review_details_without_exposing_passwords(): void
    {
        $admin = $this->makeUser(Role::ADMIN, 'workspace-admin@example.test');
        $directorRole = $this->makeRole(Role::DIRECTEUR);
        $sgRole = $this->makeRole(Role::SG);
        $direction = $this->makeDirection('Direction demandée');
        $pending = $this->makeAccountRequest([
            'requested_role_id' => $directorRole->id,
            'direction_id' => $direction->id,
            'name' => 'Martin',
            'firstname' => 'Alice',
            'email' => 'alice.pending@example.test',
            'status' => 'PENDING',
        ]);
        $rejected = $this->makeAccountRequest([
            'requested_role_id' => $sgRole->id,
            'email' => 'other.rejected@example.test',
            'status' => 'REJECTED',
        ]);

        $this->withoutMiddleware(TrackUserSession::class)
            ->actingAs($admin)
            ->get(route('admin.account-requests.index', [
                'search' => 'Alice',
                'status' => 'PENDING',
            ]))
            ->assertOk()
            ->assertSeeText('Alice')
            ->assertSeeText('Martin')
            ->assertSeeText('alice.pending@example.test')
            ->assertSeeText('Direction demandée')
            ->assertSeeText('En attente')
            ->assertDontSeeText($rejected->email)
            ->assertDontSeeText('Request-Secret-2026!');

        $this->get(route('admin.account-requests.show', $pending))
            ->assertOk()
            ->assertSeeText('Détail de la demande')
            ->assertSeeText('Rôle demandé')
            ->assertSeeText('Date de soumission')
            ->assertSeeText('Approuver')
            ->assertSeeText('Refuser')
            ->assertSee('onsubmit="return confirm(', false)
            ->assertSee('name="decision_reason"', false)
            ->assertDontSeeText('Request-Secret-2026!');
    }

    public function test_processed_request_details_show_the_reviewer_and_hide_decision_actions(): void
    {
        $admin = $this->makeUser(Role::ADMIN, 'processed-admin@example.test', 'ACTIVE', [
            'firstname' => 'Administrateur',
        ]);
        $sgRole = $this->makeRole(Role::SG);
        $createdUser = $this->makeUser(Role::SG, 'created-from-request@example.test');
        $request = $this->makeAccountRequest([
            'requested_role_id' => $sgRole->id,
            'email' => 'approved-request@example.test',
            'status' => 'APPROVED',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now()->subHour(),
            'decision_reason' => 'Demande approuvée',
            'created_user_id' => $createdUser->id,
        ]);

        $this->withoutMiddleware(TrackUserSession::class)
            ->actingAs($admin)
            ->get(route('admin.account-requests.show', $request))
            ->assertOk()
            ->assertSeeText('Approuvée')
            ->assertSeeText('Administrateur')
            ->assertSeeText('Demande approuvée')
            ->assertSeeText('Compte créé')
            ->assertDontSeeText('Approuver')
            ->assertDontSeeText('Refuser');
    }

    public function test_admin_can_search_filter_and_view_users_without_sensitive_data(): void
    {
        $admin = $this->makeUser(Role::ADMIN, 'users-admin@example.test');
        $sgRole = $this->makeRole(Role::SG);
        $activeUser = $this->makeUser(Role::SG, 'alice.users@example.test', 'ACTIVE', [
            'name' => 'Martin',
            'firstname' => 'Alice',
            'password' => 'User-Secret-2026!',
        ]);
        $inactiveUser = $this->makeUser(Role::SG, 'inactive.users@example.test', 'INACTIVE');
        $session = $this->makeSession($activeUser, 'private-session-hash-value');

        for ($index = 0; $index < 16; $index++) {
            $this->makeUser(Role::SG, 'page-'.$index.'@example.test');
        }

        $this->withoutMiddleware(TrackUserSession::class)
            ->actingAs($admin)
            ->get(route('admin.users.index', [
                'search' => 'Alice',
                'role_id' => $sgRole->id,
                'status' => 'ACTIVE',
            ]))
            ->assertOk()
            ->assertSeeText('Alice')
            ->assertSeeText('Martin')
            ->assertSeeText('alice.users@example.test')
            ->assertSeeText('Actif')
            ->assertDontSeeText($inactiveUser->email)
            ->assertDontSeeText('User-Secret-2026!')
            ->assertDontSeeText($session->session_key_hash);

        $this->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('page=2', false);

        $this->get(route('admin.users.show', $activeUser))
            ->assertOk()
            ->assertSeeText('Compte utilisateur')
            ->assertSeeText('Rôle')
            ->assertSeeText('Date de création')
            ->assertDontSeeText('User-Secret-2026!')
            ->assertDontSeeText((string) $activeUser->password)
            ->assertDontSeeText($session->session_key_hash);
    }

    public function test_admin_can_create_and_update_users_through_the_existing_service(): void
    {
        $admin = $this->makeUser(Role::ADMIN, 'create-edit-admin@example.test');
        $sgRole = $this->makeRole(Role::SG);

        $this->withoutMiddleware(TrackUserSession::class)
            ->actingAs($admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSeeText('Créer un compte')
            ->assertSee('name="password_confirmation"', false);

        $this->post(route('admin.users.store'), [
            'name' => 'Initial',
            'firstname' => 'Compte',
            'email' => 'initial.user@example.test',
            'password' => 'Initial-Password-2026!',
            'password_confirmation' => 'Initial-Password-2026!',
            'role_id' => $sgRole->id,
            'direction_id' => '',
        ])->assertRedirect(route('admin.users.index'));

        $user = User::query()->where('email', 'initial.user@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('Initial-Password-2026!', $user->password));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.created',
            'entity_type' => 'User',
        ]);

        $this->get(route('admin.users.edit', $user))
            ->assertOk()
            ->assertSeeText('Modifier un compte')
            ->assertSee('name="password_confirmation"', false)
            ->assertDontSee('value="'.$user->password.'"', false);

        $this->put(route('admin.users.update', $user), [
            'name' => 'Modifié',
            'firstname' => 'Compte',
            'email' => 'updated.user@example.test',
            'role_id' => $sgRole->id,
            'direction_id' => '',
        ])->assertRedirect(route('admin.users.show', $user));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Modifié',
            'email' => 'updated.user@example.test',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.updated',
            'entity_type' => 'User',
        ]);
        $this->assertTrue(Hash::check('Initial-Password-2026!', $user->fresh()->password));
    }

    public function test_user_activation_actions_use_d5_session_and_audit_behavior(): void
    {
        $admin = $this->makeUser(Role::ADMIN, 'activation-admin@example.test');
        $user = $this->makeUser(Role::SG, 'session-user@example.test');
        $session = $this->makeSession($user, hash('sha256', 'active-session-id'));

        $this->withoutMiddleware(TrackUserSession::class)
            ->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSeeText('Désactiver')
            ->assertSee('onsubmit="return confirm(', false);

        $this->patch(route('admin.users.deactivate', $user))
            ->assertRedirect(route('admin.users.show', $user));

        $this->assertSame('INACTIVE', $user->fresh()->status);
        $this->assertSame('REVOKED', $session->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.deactivated']);

        $this->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSeeText('Activer');

        $this->patch(route('admin.users.activate', $user))
            ->assertRedirect(route('admin.users.show', $user));

        $this->assertSame('ACTIVE', $user->fresh()->status);
        $this->assertSame('REVOKED', $session->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.activated']);
    }

    public function test_admin_can_force_logout_all_active_sessions_without_deactivating_or_deleting_history(): void
    {
        $admin = $this->makeUser(Role::ADMIN, 'force-logout-admin@example.test');
        $target = $this->makeUser(Role::SG, 'force-logout-target@example.test');
        $otherUser = $this->makeUser(Role::DIRECTEUR, 'force-logout-other@example.test');
        $inactiveUser = $this->makeUser(Role::SG, 'force-logout-inactive@example.test', 'INACTIVE');
        $firstSession = $this->makeSession($target, hash('sha256', 'target-session-one'));
        $secondSession = $this->makeSession($target, hash('sha256', 'target-session-two'));
        $historicalSession = $this->makeSession($target, hash('sha256', 'target-session-old'));
        $historicalSession->update([
            'status' => 'REVOKED',
            'revoked_at' => now()->subDay(),
        ]);
        $otherSession = $this->makeSession($otherUser, hash('sha256', 'other-user-session'));

        $this->withoutMiddleware(TrackUserSession::class)
            ->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSeeText('Actions')
            ->assertSee(route('admin.users.revoke-sessions', $target), false)
            ->assertSeeText('Déconnexion (2)')
            ->assertSee(route('admin.users.deactivate', $target), false)
            ->assertDontSee(route('admin.users.activate', $target), false)
            ->assertSee(route('admin.users.activate', $inactiveUser), false)
            ->assertDontSee(route('admin.users.deactivate', $inactiveUser), false)
            ->assertDontSee(route('admin.users.revoke-sessions', $inactiveUser), false)
            ->assertSee('onsubmit="return confirm(', false);

        $this->get(route('admin.users.show', $target))
            ->assertOk()
            ->assertSeeText('Sessions actives')
            ->assertSeeText('Forcer la déconnexion')
            ->assertSee('onsubmit="return confirm(', false);

        $this->from(route('admin.users.show', $target))
            ->patch(route('admin.users.revoke-sessions', $target))
            ->assertRedirect(route('admin.users.show', $target))
            ->assertSessionHas('status', '2 sessions actives ont été révoquées. Le compte reste actif.');

        $this->assertSame('ACTIVE', $target->fresh()->status);
        $this->assertSame('REVOKED', $firstSession->fresh()->status);
        $this->assertSame('REVOKED', $secondSession->fresh()->status);
        $this->assertSame('REVOKED', $historicalSession->fresh()->status);
        $this->assertSame('ACTIVE', $otherSession->fresh()->status);
        $this->assertSame(4, UserSession::query()->count());

        $audit = AuditLog::query()
            ->where('action', 'user.sessions_revoked')
            ->firstOrFail();
        $this->assertSame($admin->id, $audit->actor_id);
        $this->assertSame('User', $audit->entity_type);
        $this->assertSame($target->id, $audit->entity_id);
        $this->assertSame([
            'revoked_sessions' => 2,
            'result' => 'success',
        ], json_decode($audit->description, true, flags: JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('target-session-one', $audit->description);
        $this->assertStringNotContainsString($firstSession->session_key_hash, $audit->description);
    }

    public function test_force_logout_reports_and_audits_when_no_active_sessions_exist(): void
    {
        $admin = $this->makeUser(Role::ADMIN, 'no-session-admin@example.test');
        $target = $this->makeUser(Role::SG, 'no-session-target@example.test');
        $historicalSession = $this->makeSession($target, hash('sha256', 'historical-session'));
        $historicalSession->update([
            'status' => 'REVOKED',
            'revoked_at' => now()->subDay(),
        ]);

        $this->withoutMiddleware(TrackUserSession::class)
            ->actingAs($admin)
            ->get(route('admin.users.show', $target))
            ->assertOk()
            ->assertSeeText('Sessions actives')
            ->assertSeeText('0')
            ->assertDontSee(route('admin.users.revoke-sessions', $target), false);

        $this->from(route('admin.users.show', $target))
            ->patch(route('admin.users.revoke-sessions', $target))
            ->assertRedirect(route('admin.users.show', $target))
            ->assertSessionHas('status', 'Aucune session active n’a été trouvée pour ce compte.');

        $this->assertSame('ACTIVE', $target->fresh()->status);
        $this->assertSame('REVOKED', $historicalSession->fresh()->status);
        $this->assertSame(1, UserSession::query()->count());

        $audit = AuditLog::query()
            ->where('action', 'user.sessions_revoked')
            ->firstOrFail();
        $this->assertSame($admin->id, $audit->actor_id);
        $this->assertSame($target->id, $audit->entity_id);
        $this->assertSame([
            'revoked_sessions' => 0,
            'result' => 'no_active_sessions',
        ], json_decode($audit->description, true, flags: JSON_THROW_ON_ERROR));
    }

    #[DataProvider('nonAdminRoles')]
    public function test_sg_and_directeur_cannot_access_account_or_user_administration(string $roleCode): void
    {
        $user = $this->makeUser($roleCode, strtolower($roleCode).'.forbidden@example.test');
        $request = $this->makeAccountRequest(['email' => 'forbidden-request@example.test']);
        $target = $this->makeUser(Role::SG, 'forbidden-target@example.test');

        $this->withoutMiddleware(TrackUserSession::class)
            ->actingAs($user)
            ->get(route('admin.account-requests.index'))
            ->assertForbidden();

        $this->get(route('admin.account-requests.show', $request))->assertForbidden();
        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('admin.users.show', $target))->assertForbidden();
        $this->get(route('admin.users.create'))->assertForbidden();
        $session = $this->makeSession($target, hash('sha256', 'forbidden-session'));
        $this->patch(route('admin.users.revoke-sessions', $target))->assertForbidden();
        $this->assertSame('ACTIVE', $session->fresh()->status);
    }

    public function test_guest_is_redirected_from_account_and_user_administration(): void
    {
        $this->get(route('admin.account-requests.index'))->assertRedirect(route('login'));
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
        $user = $this->makeUser(Role::SG, 'guest-force-logout@example.test');
        $this->patch(route('admin.users.revoke-sessions', $user))->assertRedirect(route('login'));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function nonAdminRoles(): array
    {
        return [
            'SG' => [Role::SG],
            'DIRECTEUR' => [Role::DIRECTEUR],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeAccountRequest(array $overrides = []): AccountRequest
    {
        $role = $this->makeRole(Role::SG);

        return AccountRequest::query()->create(array_merge([
            'requested_role_id' => $role->id,
            'name' => 'Demande',
            'firstname' => 'Test',
            'email' => 'request-'.uniqid().'@example.test',
            'password' => 'Request-Secret-2026!',
            'status' => 'PENDING',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeUser(
        string $roleCode,
        string $email,
        string $status = 'ACTIVE',
        array $overrides = [],
    ): User {
        $role = $this->makeRole($roleCode);

        return User::query()->create(array_merge([
            'role_id' => $role->id,
            'name' => 'Compte',
            'firstname' => 'Test',
            'email' => $email,
            'password' => 'User-Secret-2026!',
            'status' => $status,
        ], $overrides));
    }

    private function makeRole(string $code): Role
    {
        return Role::query()->firstOrCreate(
            ['code' => $code],
            ['name' => $code],
        );
    }

    private function makeDirection(string $name): Direction
    {
        $group = Group::query()->create(['name' => 'Groupe de '.$name]);

        return Direction::query()->create([
            'group_id' => $group->id,
            'name' => $name,
        ]);
    }

    private function makeSession(User $user, string $sessionHash): UserSession
    {
        return UserSession::query()->create([
            'user_id' => $user->id,
            'session_key_hash' => $sessionHash,
            'ip_address' => '127.0.0.1',
            'device_type' => 'DESKTOP',
            'operating_system' => 'Windows',
            'status' => 'ACTIVE',
            'connected_at' => now(),
            'last_activity_at' => now(),
        ]);
    }
}
