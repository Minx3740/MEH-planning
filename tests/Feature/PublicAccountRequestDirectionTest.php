<?php

namespace Tests\Feature;

use App\Models\AccountRequest;
use App\Models\Direction;
use App\Models\Group;
use App\Models\Role;
use Tests\TestCase;

final class PublicAccountRequestDirectionTest extends TestCase
{
    public function test_form_offers_only_public_roles_and_real_directions(): void
    {
        $sgRole = $this->makeRole(Role::SG);
        $directorRole = $this->makeRole(Role::DIRECTEUR);
        $adminRole = $this->makeRole(Role::ADMIN);
        $direction = $this->makeDirection('Direction sélectionnable');

        $this->get(route('account-requests.create'))
            ->assertOk()
            ->assertSee('value="'.$sgRole->id.'"', false)
            ->assertSee('value="'.$directorRole->id.'"', false)
            ->assertSee('value="'.$direction->id.'"', false)
            ->assertDontSee('value="'.$adminRole->id.'"', false)
            ->assertSeeText('Direction sélectionnable');
    }

    public function test_director_request_accepts_an_existing_direction(): void
    {
        $directorRole = $this->makeRole(Role::DIRECTEUR);
        $direction = $this->makeDirection('Direction du candidat');

        $this->post(route('account-requests.store'), $this->requestPayload([
            'requested_role_id' => $directorRole->id,
            'direction_id' => $direction->id,
        ]))
            ->assertRedirect(route('account-requests.create'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('account_requests', [
            'email' => 'directeur-candidat@example.test',
            'requested_role_id' => $directorRole->id,
            'direction_id' => $direction->id,
            'status' => 'PENDING',
        ]);
    }

    public function test_nonexistent_direction_is_rejected_for_public_requests(): void
    {
        $directorRole = $this->makeRole(Role::DIRECTEUR);

        $this->from(route('account-requests.create'))
            ->post(route('account-requests.store'), $this->requestPayload([
                'requested_role_id' => $directorRole->id,
                'direction_id' => 999999,
            ]))
            ->assertRedirect(route('account-requests.create'))
            ->assertSessionHasErrors('direction_id');

        $this->assertSame(0, AccountRequest::query()->count());
    }

    public function test_sg_request_can_be_submitted_without_a_direction(): void
    {
        $sgRole = $this->makeRole(Role::SG);

        $this->post(route('account-requests.store'), $this->requestPayload([
            'requested_role_id' => $sgRole->id,
        ]))
            ->assertRedirect(route('account-requests.create'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('account_requests', [
            'email' => 'directeur-candidat@example.test',
            'requested_role_id' => $sgRole->id,
            'direction_id' => null,
        ]);
    }

    public function test_director_request_is_blocked_with_an_explanation_when_no_directions_exist(): void
    {
        $sgRole = $this->makeRole(Role::SG);
        $directorRole = $this->makeRole(Role::DIRECTEUR);

        $response = $this->get(route('account-requests.create'))
            ->assertOk()
            ->assertSeeText("Aucune direction n'est actuellement disponible");

        $this->assertMatchesRegularExpression(
            '/<option\s+value="'.$directorRole->id.'"\s+disabled/s',
            $response->getContent(),
        );

        $response = $this->from(route('account-requests.create'))
            ->post(route('account-requests.store'), $this->requestPayload([
                'requested_role_id' => $directorRole->id,
            ]))
            ->assertRedirect(route('account-requests.create'))
            ->assertSessionHasErrors('direction_id');

        $this->assertStringContainsString(
            "Aucune direction n'est actuellement disponible",
            $response->getSession()->get('errors')->first('direction_id'),
        );

        $this->post(route('account-requests.store'), $this->requestPayload([
            'email' => 'sg-sans-direction@example.test',
            'requested_role_id' => $sgRole->id,
        ]))
            ->assertRedirect(route('account-requests.create'))
            ->assertSessionHas('status');
    }

    public function test_admin_role_cannot_be_requested_through_a_forged_submission(): void
    {
        $this->makeRole(Role::SG);
        $adminRole = $this->makeRole(Role::ADMIN);

        $this->get(route('account-requests.create'))
            ->assertOk()
            ->assertDontSee('value="'.$adminRole->id.'"', false);

        $this->post(route('account-requests.store'), $this->requestPayload([
            'requested_role_id' => $adminRole->id,
        ]))->assertUnprocessable();

        $this->assertSame(0, AccountRequest::query()->count());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function requestPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Candidat',
            'firstname' => 'Directeur',
            'email' => 'directeur-candidat@example.test',
            'password' => 'Request-Password-2026!',
            'password_confirmation' => 'Request-Password-2026!',
            'phone' => '',
            'requested_role_id' => null,
            'direction_id' => '',
        ], $overrides);
    }

    private function makeRole(string $code): Role
    {
        return Role::query()->create([
            'code' => $code,
            'name' => $code,
        ]);
    }

    private function makeDirection(string $name): Direction
    {
        $group = Group::query()->create(['name' => 'Groupe de '.$name]);

        return Direction::query()->create([
            'group_id' => $group->id,
            'name' => $name,
        ]);
    }
}
