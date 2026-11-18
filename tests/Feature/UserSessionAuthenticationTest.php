<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Middleware\TrackUserSession;
use App\Models\Direction;
use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use App\Models\UserSession;
use App\Services\UserSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class UserSessionAuthenticationTest extends TestCase
{
    public function test_user_can_login_with_valid_credentials_and_create_a_session_record(): void
    {
        $user = $this->makeUser();

        $response = $this->post('/connexion', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/');

        $sessionRecord = UserSession::query()->where('user_id', $user->id)->first();

        $this->assertNotNull($sessionRecord);
        $this->assertSame('ACTIVE', $sessionRecord->status);
        $this->assertSame(64, strlen($sessionRecord->session_key_hash));
        $this->assertSame(hash('sha256', session()->getId()), $sessionRecord->session_key_hash);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = $this->makeUser();

        $response = $this->from(route('login'))->post('/connexion', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_user_can_logout_and_revoke_the_current_session(): void
    {
        $user = $this->makeUser();

        $session = $this->app['session'];
        $session->start();
        $sessionId = $session->getId();

        $userSession = UserSession::query()->create([
            'user_id' => $user->id,
            'session_key_hash' => hash('sha256', $sessionId),
            'ip_address' => '127.0.0.1',
            'device_type' => 'DESKTOP',
            'operating_system' => 'Linux',
            'status' => 'ACTIVE',
            'connected_at' => now(),
            'last_activity_at' => now(),
        ]);

        $request = Request::create('/deconnexion', 'POST');
        $request->setLaravelSession($session->driver());
        $request->session()->put('_token', 'logout-token');

        $this->be($user);

        $controller = new AuthenticatedSessionController(app(UserSessionService::class));
        $response = $controller->destroy($request);

        $this->assertSame(route('login'), $response->getTargetUrl());
        $this->assertDatabaseHas('user_sessions', [
            'id' => $userSession->id,
            'status' => 'REVOKED',
        ]);
    }

    public function test_revoked_session_is_rejected_by_the_session_tracker(): void
    {
        $user = $this->makeUser();

        $sessionId = 'revoked-session-id';
        $this->app['session']->setId($sessionId);
        $this->be($user);

        UserSession::query()->create([
            'user_id' => $user->id,
            'session_key_hash' => hash('sha256', $sessionId),
            'ip_address' => '127.0.0.1',
            'device_type' => 'DESKTOP',
            'operating_system' => 'Linux',
            'status' => 'REVOKED',
            'connected_at' => now(),
            'last_activity_at' => now(),
            'revoked_at' => now(),
        ]);

        $response = $this->get('/');

        $response->assertStatus(401);
    }

    public function test_user_can_only_revoke_own_sessions(): void
    {
        $owner = $this->makeUser('owner@example.com');
        $otherUser = $this->makeUser('other@example.com');

        $this->app['session']->start();
        $currentSessionId = $this->app['session']->getId();

        UserSession::query()->create([
            'user_id' => $owner->id,
            'session_key_hash' => hash('sha256', $currentSessionId),
            'ip_address' => '127.0.0.1',
            'device_type' => 'DESKTOP',
            'operating_system' => 'Linux',
            'status' => 'ACTIVE',
            'connected_at' => now(),
            'last_activity_at' => now(),
        ]);

        $this->be($owner);

        $otherSession = UserSession::query()->create([
            'user_id' => $otherUser->id,
            'session_key_hash' => hash('sha256', 'other-session-id'),
            'ip_address' => '127.0.0.1',
            'device_type' => 'MOBILE',
            'operating_system' => 'Android',
            'status' => 'ACTIVE',
            'connected_at' => now(),
            'last_activity_at' => now(),
        ]);

        $response = $this->withoutMiddleware(TrackUserSession::class)
            ->from(route('sessions.index'))
            ->post(route('sessions.revoke', $otherSession));

        $response->assertStatus(403);
        $this->assertDatabaseHas('user_sessions', [
            'id' => $otherSession->id,
            'status' => 'ACTIVE',
        ]);
    }

    public function test_user_can_list_only_their_own_sessions(): void
    {
        $currentUser = $this->makeUser('current@example.com');
        $otherUser = $this->makeUser('someone-else@example.com');

        $this->app['session']->start();
        $currentSessionId = $this->app['session']->getId();

        $currentSession = UserSession::query()->create([
            'user_id' => $currentUser->id,
            'session_key_hash' => hash('sha256', $currentSessionId),
            'ip_address' => '127.0.0.1',
            'device_type' => 'DESKTOP',
            'operating_system' => 'Windows',
            'status' => 'ACTIVE',
            'connected_at' => now(),
            'last_activity_at' => now(),
        ]);

        $this->be($currentUser);

        UserSession::query()->create([
            'user_id' => $otherUser->id,
            'session_key_hash' => hash('sha256', 'foreign-session'),
            'ip_address' => '10.0.0.5',
            'device_type' => 'TABLET',
            'operating_system' => 'iOS',
            'status' => 'ACTIVE',
            'connected_at' => now(),
            'last_activity_at' => now(),
        ]);

        $response = $this->withoutMiddleware(TrackUserSession::class)
            ->get(route('sessions.index'));

        $response->assertOk();
        $response->assertSee('Mes sessions');
        $response->assertSeeText($currentSession->device_type);
        $response->assertDontSeeText('10.0.0.5');
    }

    private function makeUser(string $email = 'user@example.com'): User
    {
        $group = Group::query()->firstOrCreate([
            'name' => 'Direction générale',
        ]);

        $direction = Direction::query()->firstOrCreate([
            'group_id' => $group->id,
            'name' => 'Pilotage',
        ]);

        $role = Role::query()->firstOrCreate([
            'code' => 'ADMIN',
            'name' => 'Administrateur',
        ]);

        return User::query()->create([
            'role_id' => $role->id,
            'direction_id' => $direction->id,
            'name' => 'Doe',
            'firstname' => 'John',
            'email' => $email,
            'password' => Hash::make('password'),
            'status' => 'ACTIVE',
        ]);
    }
}
