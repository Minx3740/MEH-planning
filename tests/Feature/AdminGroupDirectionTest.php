<?php

namespace Tests\Feature;

use App\Http\Middleware\TrackUserSession;
use App\Models\Direction;
use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class AdminGroupDirectionTest extends TestCase
{
    public function test_admin_can_manage_groups_and_view_their_directions(): void
    {
        $admin = $this->makeUser('ADMIN');
        $group = Group::query()->create([
            'name' => 'Groupe existant',
            'description' => 'Description du groupe',
        ]);
        Direction::query()->create([
            'group_id' => $group->id,
            'name' => 'Direction existante',
            'code' => 'DIR-EX',
        ]);

        $this->withoutMiddleware(TrackUserSession::class)
            ->actingAs($admin)
            ->get(route('admin.groups.index'))
            ->assertOk()
            ->assertSeeText('Groupe existant')
            ->assertSeeText('Direction existante');

        $this->get(route('admin.groups.create'))
            ->assertOk()
            ->assertSeeText('Nom du groupe');

        $this->post(route('admin.groups.store'), [
            'name' => 'Nouveau groupe',
            'description' => 'Description nouvelle',
        ])->assertRedirect(route('admin.groups.index'));

        $newGroup = Group::query()->where('name', 'Nouveau groupe')->firstOrFail();
        $this->assertSame('Description nouvelle', $newGroup->description);

        $this->get(route('admin.groups.edit', $newGroup))
            ->assertOk()
            ->assertSeeText('Modifier un groupe');

        $this->put(route('admin.groups.update', $newGroup), [
            'name' => 'Groupe modifié',
            'description' => null,
        ])->assertRedirect(route('admin.groups.index'));

        $this->assertDatabaseHas('groups', [
            'id' => $newGroup->id,
            'name' => 'Groupe modifié',
            'description' => null,
        ]);
    }

    public function test_group_names_are_required_and_unique(): void
    {
        $admin = $this->makeUser('ADMIN');
        Group::query()->create(['name' => 'Groupe déjà présent']);

        $this->withoutMiddleware(TrackUserSession::class)
            ->actingAs($admin)
            ->from(route('admin.groups.create'))
            ->post(route('admin.groups.store'), ['name' => 'Groupe déjà présent'])
            ->assertRedirect(route('admin.groups.create'))
            ->assertSessionHasErrors('name');

        $this->from(route('admin.groups.create'))
            ->post(route('admin.groups.store'), ['name' => ''])
            ->assertRedirect(route('admin.groups.create'))
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Group::query()->count());
    }

    public function test_admin_can_create_and_update_directions_with_existing_groups(): void
    {
        $admin = $this->makeUser('ADMIN');
        $firstGroup = Group::query()->create(['name' => 'Premier groupe']);
        $secondGroup = Group::query()->create(['name' => 'Second groupe']);

        $this->withoutMiddleware(TrackUserSession::class)
            ->actingAs($admin)
            ->get(route('admin.directions.index'))
            ->assertOk();

        $this->get(route('admin.directions.create'))
            ->assertOk()
            ->assertSeeText('Premier groupe');

        $this->post(route('admin.directions.store'), [
            'group_id' => $firstGroup->id,
            'name' => 'Direction créée',
            'code' => 'DIR-NEW',
            'description' => 'Description direction',
        ])->assertRedirect(route('admin.directions.index'));

        $direction = Direction::query()->where('code', 'DIR-NEW')->firstOrFail();
        $this->assertSame($firstGroup->id, $direction->group_id);

        $this->get(route('admin.directions.edit', $direction))
            ->assertOk()
            ->assertSee('value="Direction créée"', false);

        $this->put(route('admin.directions.update', $direction), [
            'group_id' => $secondGroup->id,
            'name' => 'Direction modifiée',
            'code' => 'DIR-UPDATED',
            'description' => null,
        ])->assertRedirect(route('admin.directions.index'));

        $this->assertDatabaseHas('directions', [
            'id' => $direction->id,
            'group_id' => $secondGroup->id,
            'name' => 'Direction modifiée',
            'code' => 'DIR-UPDATED',
        ]);
    }

    public function test_direction_requires_an_existing_group_and_unique_values(): void
    {
        $admin = $this->makeUser('ADMIN');
        $group = Group::query()->create(['name' => 'Groupe de validation']);
        Direction::query()->create([
            'group_id' => $group->id,
            'name' => 'Direction réservée',
            'code' => 'DIR-TAKEN',
        ]);

        $this->withoutMiddleware(TrackUserSession::class)
            ->actingAs($admin)
            ->from(route('admin.directions.create'))
            ->post(route('admin.directions.store'), [
                'group_id' => 999999,
                'name' => 'Direction invalide',
            ])
            ->assertRedirect(route('admin.directions.create'))
            ->assertSessionHasErrors('group_id');

        $this->from(route('admin.directions.create'))
            ->post(route('admin.directions.store'), [
                'group_id' => $group->id,
                'name' => 'Direction réservée',
                'code' => 'DIR-OTHER',
            ])
            ->assertRedirect(route('admin.directions.create'))
            ->assertSessionHasErrors('name');

        $this->from(route('admin.directions.create'))
            ->post(route('admin.directions.store'), [
                'group_id' => $group->id,
                'name' => 'Direction distincte',
                'code' => 'DIR-TAKEN',
            ])
            ->assertRedirect(route('admin.directions.create'))
            ->assertSessionHasErrors('code');

        $this->assertSame(1, Direction::query()->count());
    }

    public function test_direction_form_is_unavailable_until_a_group_exists(): void
    {
        $admin = $this->makeUser('ADMIN');

        $this->withoutMiddleware(TrackUserSession::class)
            ->actingAs($admin)
            ->get(route('admin.directions.create'))
            ->assertOk()
            ->assertSeeText("Aucun groupe n'est enregistré.")
            ->assertSee(route('admin.groups.create'), false)
            ->assertSee('disabled', false);
    }

    #[DataProvider('nonAdminRoles')]
    public function test_only_admins_can_manage_groups_and_directions(string $roleCode): void
    {
        $user = $this->makeUser($roleCode);
        $group = Group::query()->create(['name' => 'Groupe protégé']);

        $this->withoutMiddleware(TrackUserSession::class)
            ->actingAs($user)
            ->get(route('admin.groups.index'))
            ->assertForbidden();

        $this->post(route('admin.groups.store'), ['name' => 'Tentative'])
            ->assertForbidden();

        $this->get(route('admin.directions.index'))
            ->assertForbidden();

        $this->post(route('admin.directions.store'), [
            'group_id' => $group->id,
            'name' => 'Direction interdite',
        ])->assertForbidden();

        $this->assertDatabaseMissing('groups', ['name' => 'Tentative']);
        $this->assertDatabaseMissing('directions', ['name' => 'Direction interdite']);
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

    private function makeUser(string $roleCode): User
    {
        $role = Role::query()->create([
            'code' => $roleCode,
            'name' => $roleCode,
        ]);

        return User::query()->create([
            'role_id' => $role->id,
            'name' => 'Test',
            'firstname' => $roleCode,
            'email' => strtolower($roleCode).'.group-direction@example.test',
            'password' => 'Test-Password-2026!',
            'status' => 'ACTIVE',
        ]);
    }
}
