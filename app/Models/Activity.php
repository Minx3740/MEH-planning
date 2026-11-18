<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'direction_id',
        'created_by',
        'title',
        'description',
        'location',
        'start_at',
        'end_at',
        'has_time',
        'priority',
        'status',
        'archived_at',
        'archived_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'has_time' => 'boolean',
            'archived_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function archiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function people(): HasMany
    {
        return $this->hasMany(ActivityPerson::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ActivityVersion::class);
    }

    public function reschedules(): HasMany
    {
        return $this->hasMany(ActivityReschedule::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ActivityAttachment::class);
    }
}
