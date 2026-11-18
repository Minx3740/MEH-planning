<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExerciceCompte extends Model
{
    protected $table = 'exercice_comptes';

    protected $fillable = [
        'nom',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];
}
