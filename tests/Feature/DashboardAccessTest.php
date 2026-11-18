<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Middleware\TrackUserSession;
use App\Models\Role;
use App\Models\User;
use App\Models\UserSession;
use App\Services\UserSessionService;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class DashboardAccessTest extends TestCase
{
    public function test_admin_can_access_the_admin_dashboard_and_see_available_navigation(): void
    {
        $admin = $this->makeUser('ADMIN');

        $response = $this->withoutMiddleware(TrackUserSession::class)
            ->actingAs($admin)
            ->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSeeText('Tableau de bord')
            ->assertSeeText('Gestion des utilisateurs')
            ->assertSeeText('Demandes de comptes')
            ->assertSeeText('Groupes')
            ->assertSeeText('Directions')
            ->assertSeeText("Journaux d'activité")
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('name="_token"', false);
    }

    #[DataProvider('nonAdminRoles')]
    public function test_sg_and_directeur_cannot_access_the_admin_dashboard(string $roleCode): void
    {
        $user = $this->makeUser($roleCode);

        $this->withoutMiddleware(TrackUserSession::class)
            ->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function nonAdminRoles(): array
    {
        return [
            'SG' => ['SG'],
            'DIRECTEUR' => ['DIRECTEUR'],
        ];
    }

    #[DataProvider('roleHomes')]
    public function test_login_redirects_each_role_to_its_own_home(
        string $roleCode,
        string $dashboardRoute,
        string $expectedHeading,
    ): void {
        $user = $this->makeUser($roleCode);

        $this->withoutMiddleware(TrackUserSession::class)
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'Login-Test-Password-2026!',
            ])
            ->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))
            ->assertRedirect(route($dashboardRoute));

        $this->get(route($dashboardRoute))
            ->assertOk()
            ->assertSeeText($expectedHeading);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function roleHomes(): array
    {
        return [
            'ADMIN' => ['ADMIN', 'admin.dashboard', 'Tableau de bord'],
            'SG' => ['SG', 'sg.dashboard', 'Accueil du Secrétariat général'],
            'DIRECTEUR' => ['DIRECTEUR', 'director.dashboard', 'Accueil de la direction'],
        ];
    }

    public function test_guest_is_redirected_to_login_from_the_admin_dashboard(): void
    {
        $this->withoutMiddleware(TrackUserSession::class)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_public_pages_use_the_public_layout(): void
    {
        $this->get(route('public.home'))
            ->assertOk()
            ->assertSeeText('Gestion des plannings MEH')
            ->assertSee(route('login'), false)
            ->assertSee(route('account-requests.create'), false);

        $this->get(route('login'))
            ->assertOk()
            ->assertSeeText('Connexion')
            ->assertSee('action="'.route('login.store').'"', false)
            ->assertSee('name="_token"', false);

        $this->get(route('account-requests.create'))
            ->assertOk()
            ->assertSeeText('Demande de création de compte')
            ->assertSee('action="'.route('account-requests.store').'"', false);
    }

    public function test_logout_revokes_only_the_current_session(): void
    {
        $user = $this->makeUser('ADMIN');
        $session = $this->app['session'];
        $session->start();
        $sessionId = $session->getId();

        $this->be($user);
        $currentSession = UserSession::query()->create([
            'user_id' => $user->id,
            'session_key_hash' => hash('sha256', $sessionId),
            'ip_address' => '127.0.0.1',
            'device_type' => 'DESKTOP',
            'operating_system' => 'Windows',
            'status' => 'ACTIVE',
            'connected_at' => now(),
            'last_activity_at' => now(),
        ]);

        $otherSession = UserSession::query()->create([
            'user_id' => $user->id,
            'session_key_hash' => hash('sha256', 'another-session-that-must-remain-active'),
            'ip_address' => '127.0.0.2',
            'device_type' => 'DESKTOP',
            'operating_system' => 'Windows',
            'status' => 'ACTIVE',
            'connected_at' => now(),
            'last_activity_at' => now(),
        ]);

        $request = Request::create('/deconnexion', 'POST');
        $request->setLaravelSession($session->driver());
        $request->session()->put('_token', 'logout-token');
        $controller = new AuthenticatedSessionController(app(UserSessionService::class));
        $response = $controller->destroy($request);

        $this->assertSame(route('login'), $response->getTargetUrl());
        $this->assertGuest();
        $this->assertDatabaseHas('user_sessions', [
            'id' => $currentSession->id,
            'status' => 'REVOKED',
        ]);
        $this->assertDatabaseHas('user_sessions', [
            'id' => $otherSession->id,
            'status' => 'ACTIVE',
        ]);
    }

    private function makeUser(string $roleCode): User
    {
        $role = Role::query()->create([
            'code' => $roleCode,
            'name' => $roleCode,
            'description' => 'Dashboard access test role',
        ]);

        return User::query()->create([
            'role_id' => $role->id,
            'direction_id' => null,
            'name' => 'MEH',
            'firstname' => 'Test '.$roleCode,
            'email' => strtolower($roleCode).'.dashboard@example.test',
            'password' => 'Login-Test-Password-2026!',
            'status' => 'ACTIVE',
        ]);
    }
}
