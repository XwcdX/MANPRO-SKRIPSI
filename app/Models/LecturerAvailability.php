<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LecturerAvailability Model
 * 
 * Tracks lecturer availability for specific time slots within period schedules.
 * Lecturers mark specific date + time_slot combinations as available/unavailable.
 * 
 * @property string $id
 * @property string $lecturer_id
 * @property string $period_schedule_id
 * @property string $type (proposal|thesis)
 * @property \Carbon\Carbon $date
 * @property string $time_slot (e.g., '08:00-08:45')
 * @property bool $is_available
 */
class LecturerAvailability extends Model
{
    protected $table = 'lecturer_availability';

    protected $fillable = [
        'lecturer_id',
        'period_schedule_id',
        'type',
        'date',
        'time_slot',
        'is_available',
    ];

    protected $casts = [
        'date' => 'date',
        'is_available' => 'boolean',
    ];

    /**
     * Get the lecturer that owns this availability.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class);
    }

    /**
     * Get the period schedule that this availability belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function periodSchedule(): BelongsTo
    {
        return $this->belongsTo(PeriodSchedule::class);
    }

    /**
     * Scope to get only available slots.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope to get only unavailable slots.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnavailable($query)
    {
        return $query->where('is_available', false);
    }
}
