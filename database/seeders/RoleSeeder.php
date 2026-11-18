<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed the roles required by the application.
     */
    public function run(): void
    {
        $roles = [
            [
                'code' => 'ADMIN',
                'name' => 'Administrateur',
                'description' => "Administration technique de l'application.",
            ],
            [
                'code' => 'SG',
                'name' => 'Secrétariat général',
                'description' => 'Consultation transversale des plannings de toutes les directions.',
            ],
            [
                'code' => 'DIRECTEUR',
                'name' => 'Directeur',
                'description' => 'Accès limité au planning de sa propre direction.',
            ],
        ];

        foreach ($roles as $role) {
            Role::query()->firstOrCreate(
                ['code' => $role['code']],
                [
                    'name' => $role['name'],
                    'description' => $role['description'],
                ],
            );
        }
    }
}
