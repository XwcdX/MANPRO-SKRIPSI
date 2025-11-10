<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LecturerPeriodQuota extends Model
{
    protected $fillable = [
        'lecturer_id',
        'period_id',
        'max_students',
    ];

    protected $casts = [
        'max_students' => 'integer',
    ];

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }
}
