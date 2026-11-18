<?php

namespace Tests\Feature;

use App\Http\Middleware\TrackUserSession;
use App\Models\AccountRequest;
use App\Models\Direction;
use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use App\Models\UserSession;
use App\Services\AccountAuditService;
use App\Services\AccountRequestService;
use App\Services\UserAccountService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

final class D5AccountManagementTest extends TestCase
{
    public function test_public_account_request_is_validated_and_approved(): void
    {
        $group = Group::query()->firstOrCreate(['name' => 'Groupe test']);
        $direction = Direction::query()->firstOrCreate([
            'group_id' => $group->id,
            'name' => 'Direction test',
        ]);

        $roleSg = Role::query()->firstOrCreate(['code' => 'SG', 'name' => 'Secrétariat général']);
        $roleAdmin = Role::query()->firstOrCreate(['code' => 'ADMIN', 'name' => 'Administrateur']);

        $admin = User::query()->create([
            'role_id' => $roleAdmin->id,
            'direction_id' => null,
            'name' => 'Admin',
            'firstname' => 'System',
            'email' => 'admin@d5.test',
            'password' => Hash::make('password'),
            'status' => 'ACTIVE',
        ]);

        $request = app(AccountRequestService::class)->createRequest([
            'name' => 'Dupont',
            'firstname' => 'Alice',
            'email' => 'alice@d5.test',
            'password' => 'secret123',
            'phone' => '0123456789',
            'requested_role_id' => $roleSg->id,
            'direction_id' => $direction->id,
        ]);

        $this->assertSame('PENDING', $request->status);
        $this->assertTrue(Hash::check('secret123', $request->password));

        app(AccountRequestService::class)->approve($request, $admin);

        $user = User::query()->where('email', 'alice@d5.test')->firstOrFail();

        $this->assertSame('SG', $user->role->code);
        $this->assertSame($direction->id, $user->direction_id);
        $this->assertSame('APPROVED', $request->fresh()->status);
        $this->assertSame($admin->id, $request->fresh()->reviewed_by);
        $this->assertSame($user->id, $request->fresh()->created_user_id);
        $this->assertSame($admin->id, $request->fresh()->reviewer->id);
        $this->assertSame($user->id, $request->fresh()->createdUser->id);
        $this->assertSame($request->fresh()->password, $user->password);
        $this->assertTrue(Hash::check('secret123', $user->password));
    }

    public function test_admin_request_routes_show_approve_and_reject_the_selected_request(): void
    {
        $admin = $this->makeUser('ADMIN', 'routes-admin@d5.test');
        $first = $this->makeAccountRequest('first-request@d5.test');
        $second = $this->makeAccountRequest('second-request@d5.test');

        $this->withoutMiddleware(TrackUserSession::class);
        $this->actingAs($admin);

        $this->get(route('admin.account-requests.show', $first))
            ->assertOk()
            ->assertSeeText($first->email)
            ->assertDontSeeText($second->email);

        $this->post(route('admin.account-requests.approve', $first))
            ->assertRedirect(route('admin.account-requests.show', $first));

        $approved = $first->fresh();
        $createdUser = User::query()->findOrFail($approved->created_user_id);
        $this->assertSame('APPROVED', $approved->status);
        $this->assertSame($createdUser->id, $approved->createdUser->id);
        $this->assertSame($admin->id, $approved->reviewer->id);
        $this->assertSame('PENDING', $second->fresh()->status);

        $this->from(route('admin.account-requests.show', $second))
            ->post(route('admin.account-requests.reject', $second), ['decision_reason' => 'Dossier incomplet'])
            ->assertRedirect(route('admin.account-requests.show', $second));

        $this->assertSame('REJECTED', $second->fresh()->status);
        $this->assertSame($admin->id, $second->fresh()->reviewed_by);
        $this->assertSame('Dossier incomplet', $second->fresh()->decision_reason);
        $this->assertDatabaseMissing('users', ['email' => $second->email]);
    }

    public function test_only_admins_can_access_account_administration_and_processed_requests_cannot_be_decided_again(): void
    {
        $sg = $this->makeUser('SG', 'routes-sg@d5.test');
        $accountRequest = $this->makeAccountRequest('processed@d5.test', 'REJECTED');

        $this->withoutMiddleware(TrackUserSession::class);
        $this->actingAs($sg);

        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('admin.account-requests.show', $accountRequest))->assertForbidden();
        $this->post(route('admin.account-requests.approve', $accountRequest))->assertForbidden();
        $this->post(route('admin.account-requests.reject', $accountRequest))->assertForbidden();

        $this->actingAs($this->makeUser('ADMIN', 'processed-admin@d5.test'));
        $this->post(route('admin.account-requests.approve', $accountRequest))->assertForbidden();
        $this->assertSame('REJECTED', $accountRequest->fresh()->status);
        $this->assertDatabaseMissing('users', ['email' => $accountRequest->email]);
    }

    public function test_guest_is_redirected_from_administration(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_create_an_admin_account_directly(): void
    {
        $admin = $this->makeUser('ADMIN', 'creator-admin@d5.test');
        $adminRole = Role::query()->where('code', 'ADMIN')->firstOrFail();

        $this->withoutMiddleware(TrackUserSession::class);
        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'New',
                'firstname' => 'Administrator',
                'email' => 'new-admin@d5.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role_id' => $adminRole->id,
            ])
            ->assertRedirect(route('admin.users.index'));

        $created = User::query()->where('email', 'new-admin@d5.test')->firstOrFail();
        $this->assertSame('ADMIN', $created->role->code);
        $this->assertTrue(Hash::check('password123', $created->password));
    }

    public function test_duplicate_pending_account_request_is_rejected(): void
    {
        $role = Role::query()->firstOrCreate(['code' => 'SG'], ['name' => 'Secrétariat général']);
        $attributes = [
            'name' => 'Request',
            'firstname' => 'Test',
            'email' => 'duplicate-pending@d5.test',
            'password' => 'password123',
            'requested_role_id' => $role->id,
        ];

        app(AccountRequestService::class)->createRequest($attributes);

        $this->expectException(ValidationException::class);

        app(AccountRequestService::class)->createRequest($attributes);
    }

    public function test_director_assignment_is_unique_per_direction(): void
    {
        $group = Group::query()->firstOrCreate(['name' => 'Groupe directeur']);
        $direction = Direction::query()->firstOrCreate([
            'group_id' => $group->id,
            'name' => 'Direction unique',
        ]);

        $directorRole = Role::query()->firstOrCreate(['code' => 'DIRECTEUR', 'name' => 'Directeur']);
        $adminRole = Role::query()->firstOrCreate(['code' => 'ADMIN', 'name' => 'Administrateur']);

        User::query()->create([
            'role_id' => $directorRole->id,
            'direction_id' => $direction->id,
            'name' => 'Uno',
            'firstname' => 'Alpha',
            'email' => 'alpha@d5.test',
            'password' => Hash::make('password'),
            'status' => 'ACTIVE',
        ]);

        $this->expectException(ValidationException::class);

        app(UserAccountService::class)->create([
            'name' => 'Deux',
            'firstname' => 'Bravo',
            'email' => 'bravo@d5.test',
            'password' => 'password123',
            'role_id' => $directorRole->id,
            'direction_id' => $direction->id,
            'allow_admin' => true,
        ]);
    }

    public function test_reactivation_of_a_director_is_rejected_when_another_director_is_active(): void
    {
        $direction = $this->makeDirection('Réactivation directeur');
        $activeDirector = $this->makeUser('DIRECTEUR', 'active-director@d5.test', $direction, 'ACTIVE');
        $inactiveDirector = $this->makeUser('DIRECTEUR', 'inactive-director@d5.test', $direction, 'INACTIVE');

        $this->expectException(ValidationException::class);

        app(UserAccountService::class)->activate($inactiveDirector);
    }

    public function test_approval_rejects_a_director_request_when_the_direction_is_occupied(): void
    {
        $direction = $this->makeDirection('Approbation directeur');
        $admin = $this->makeUser('ADMIN', 'director-approval-admin@d5.test');
        $this->makeUser('DIRECTEUR', 'existing-approval-director@d5.test', $direction);
        $accountRequest = $this->makeAccountRequest(
            'requested-director@d5.test',
            'PENDING',
            'DIRECTEUR',
            $direction,
        );

        try {
            app(AccountRequestService::class)->approve($accountRequest, $admin);
            $this->fail('Approval must fail while another active director occupies the direction.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('direction_id', $exception->errors());
        }

        $this->assertSame('PENDING', $accountRequest->fresh()->status);
        $this->assertDatabaseMissing('users', ['email' => $accountRequest->email]);
    }

    public function test_reassignment_and_deactivation_release_director_directions(): void
    {
        $firstDirection = $this->makeDirection('Transfert direction A');
        $secondDirection = $this->makeDirection('Transfert direction B');
        $director = $this->makeUser('DIRECTEUR', 'transfer-director@d5.test', $firstDirection);
        $role = Role::query()->where('code', 'DIRECTEUR')->firstOrFail();

        app(UserAccountService::class)->update($director, [
            'name' => $director->name,
            'firstname' => $director->firstname,
            'email' => $director->email,
            'role_id' => $role->id,
            'direction_id' => $secondDirection->id,
        ]);

        $this->assertSame($secondDirection->id, $director->fresh()->direction_id);

        $replacement = app(UserAccountService::class)->create([
            'name' => 'Remplaçant',
            'firstname' => 'Test',
            'email' => 'replacement-director@d5.test',
            'password' => 'password123',
            'role_id' => $role->id,
            'direction_id' => $firstDirection->id,
        ]);

        app(UserAccountService::class)->deactivate($replacement);

        $newDirector = app(UserAccountService::class)->create([
            'name' => 'Nouveau',
            'firstname' => 'Directeur',
            'email' => 'new-director@d5.test',
            'password' => 'password123',
            'role_id' => $role->id,
            'direction_id' => $firstDirection->id,
        ]);

        $this->assertSame($firstDirection->id, $newDirector->direction_id);
    }

    public function test_role_change_to_director_obeys_direction_uniqueness(): void
    {
        $direction = $this->makeDirection('Changement de rôle');
        $sg = $this->makeUser('SG', 'sg-to-director@d5.test');
        $director = $this->makeUser('DIRECTEUR', 'existing-director@d5.test', $direction);
        $directorRole = Role::query()->where('code', 'DIRECTEUR')->firstOrFail();

        $this->expectException(ValidationException::class);

        app(UserAccountService::class)->update($sg, [
            'name' => $sg->name,
            'firstname' => $sg->firstname,
            'email' => $sg->email,
            'role_id' => $directorRole->id,
            'direction_id' => $direction->id,
        ]);
    }

    public function test_approval_rolls_back_account_and_request_when_audit_write_fails(): void
    {
        $admin = $this->makeUser('ADMIN', 'rollback-admin@d5.test');
        $accountRequest = $this->makeAccountRequest('rollback-request@d5.test');
        $audit = \Mockery::mock(AccountAuditService::class);
        $audit->shouldReceive('record')->once()->andThrow(new RuntimeException('audit unavailable'));
        $this->app->instance(AccountAuditService::class, $audit);

        try {
            app(AccountRequestService::class)->approve($accountRequest, $admin);
            $this->fail('The audit failure should abort approval.');
        } catch (RuntimeException $exception) {
            $this->assertSame('audit unavailable', $exception->getMessage());
        }

        $this->assertSame('PENDING', $accountRequest->fresh()->status);
        $this->assertDatabaseMissing('users', ['email' => $accountRequest->email]);
    }

    public function test_user_deactivation_revokes_all_active_sessions(): void
    {
        $group = Group::query()->firstOrCreate(['name' => 'Groupe sessions']);
        $direction = Direction::query()->firstOrCreate([
            'group_id' => $group->id,
            'name' => 'Direction sessions',
        ]);

        $sgRole = Role::query()->firstOrCreate(['code' => 'SG', 'name' => 'Secrétariat général']);
        $user = User::query()->create([
            'role_id' => $sgRole->id,
            'direction_id' => $direction->id,
            'name' => 'User',
            'firstname' => 'Active',
            'email' => 'active@d5.test',
            'password' => Hash::make('password'),
            'status' => 'ACTIVE',
        ]);

        $session = UserSession::query()->create([
            'user_id' => $user->id,
            'session_key_hash' => hash('sha256', 'd5-session-id'),
            'ip_address' => '127.0.0.1',
            'device_type' => 'DESKTOP',
            'operating_system' => 'Linux',
            'status' => 'ACTIVE',
            'connected_at' => now(),
            'last_activity_at' => now(),
        ]);
        $secondSession = UserSession::query()->create([
            'user_id' => $user->id,
            'session_key_hash' => hash('sha256', 'd5-second-session-id'),
            'ip_address' => '127.0.0.2',
            'device_type' => 'MOBILE',
            'operating_system' => 'Android',
            'status' => 'ACTIVE',
            'connected_at' => now(),
            'last_activity_at' => now(),
        ]);

        app(UserAccountService::class)->deactivate($user);

        $this->assertSame('INACTIVE', $user->fresh()->status);
        $this->assertSame('REVOKED', $session->fresh()->status);
        $this->assertSame('REVOKED', $secondSession->fresh()->status);

        app(UserAccountService::class)->activate($user);

        $this->assertSame('ACTIVE', $user->fresh()->status);
        $this->assertSame('REVOKED', $session->fresh()->status);
        $this->assertSame('REVOKED', $secondSession->fresh()->status);
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = $this->makeUser('SG', 'inactive-login@d5.test', null, 'INACTIVE');

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('user_sessions', ['user_id' => $user->id]);
    }

    public function test_account_audit_events_include_changes_without_credential_material(): void
    {
        $admin = $this->makeUser('ADMIN', 'audit-admin@d5.test');
        $sg = $this->makeUser('SG', 'audit-sg@d5.test');
        $role = Role::query()->where('code', 'SG')->firstOrFail();

        app(UserAccountService::class)->update($sg, [
            'name' => 'Updated',
            'firstname' => $sg->firstname,
            'email' => $sg->email,
            'role_id' => $role->id,
        ], $admin);

        $log = DB::table('audit_logs')->where('action', 'user.updated')->first();

        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->actor_id);
        $this->assertSame('User', $log->entity_type);
        $this->assertStringContainsString('Updated', $log->description);
        $this->assertStringNotContainsString('password', strtolower($log->description));
        $this->assertStringNotContainsString('session_key_hash', $log->description);
    }

    private function makeAccountRequest(
        string $email,
        string $status = 'PENDING',
        string $roleCode = 'SG',
        ?Direction $direction = null,
    ): AccountRequest {
        $role = Role::query()->firstOrCreate(['code' => $roleCode], ['name' => $roleCode]);

        return AccountRequest::query()->create([
            'direction_id' => $direction?->id,
            'requested_role_id' => $role->id,
            'name' => 'Request',
            'firstname' => 'Test',
            'email' => $email,
            'password' => Hash::make('password123'),
            'status' => $status,
        ]);
    }

    private function makeUser(string $roleCode, string $email, ?Direction $direction = null, string $status = 'ACTIVE'): User
    {
        $role = Role::query()->firstOrCreate(['code' => $roleCode], ['name' => $roleCode]);

        return User::query()->create([
            'role_id' => $role->id,
            'direction_id' => $direction?->id,
            'name' => 'Test',
            'firstname' => 'User',
            'email' => $email,
            'password' => Hash::make('password123'),
            'status' => $status,
        ]);
    }

    private function makeDirection(string $name): Direction
    {
        $group = Group::query()->firstOrCreate(['name' => 'D5 group '.$name]);

        return Direction::query()->firstOrCreate([
            'group_id' => $group->id,
            'name' => $name,
        ]);
    }
}
