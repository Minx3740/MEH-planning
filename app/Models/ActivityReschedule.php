<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityReschedule extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'activity_id',
        'changed_by',
        'previous_start_at',
        'previous_end_at',
        'new_start_at',
        'new_end_at',
        'reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'previous_start_at' => 'datetime',
            'previous_end_at' => 'datetime',
            'new_start_at' => 'datetime',
            'new_end_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
