<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const ADMIN = 'ADMIN';

    public const SG = 'SG';

    public const DIRECTEUR = 'DIRECTEUR';

    protected $fillable = [
        'code',
        'name',
        'description',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function accountRequests(): HasMany
    {
        return $this->hasMany(AccountRequest::class, 'requested_role_id');
    }

    public function isAdmin(): bool
    {
        return strtoupper((string) $this->code) === self::ADMIN;
    }

    public function isSg(): bool
    {
        return strtoupper((string) $this->code) === self::SG;
    }

    public function isDirector(): bool
    {
        return strtoupper((string) $this->code) === self::DIRECTEUR;
    }
}
