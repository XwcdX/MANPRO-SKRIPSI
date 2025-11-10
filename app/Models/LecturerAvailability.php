<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LecturerAvailability extends Model
{
    protected $table = 'lecturer_availability';

    protected $fillable = [
        'lecturer_id',
        'period_id',
        'type',
        'date',
        'time_slot',
        'is_available',
    ];

    protected $casts = [
        'date' => 'date',
        'is_available' => 'boolean',
    ];

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function period()
    {
        return $this->belongsTo(Period::class);
    }
}
