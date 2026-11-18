<?php

namespace Database\Seeders;

use App\Models\ExerciceCompte;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ExerciceCompteSeeder extends Seeder
{
    public function run(): void
    {
        $nom = 'Laro';
        $email = 'test@gmail.com';
        $password = 'Test123456';
        // Crée le compte d'ici
        ExerciceCompte::create([
            'nom' => $nom,
            'email' => $email,
            'password' => Hash::make($password),
        ]);
    }
}
