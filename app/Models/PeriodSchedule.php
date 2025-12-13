<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PeriodSchedule Model
 * 
 * Represents individual proposal hearing or thesis defense schedule sessions within a period.
 * Each period can have multiple schedules (e.g., 3 proposal hearings, 2 thesis defenses).
 * 
 * @property string $id
 * @property string $period_id
 * @property string $type (proposal_hearing|thesis_defense)
 * @property \Carbon\Carbon $start_date
 * @property \Carbon\Carbon $end_date
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PeriodSchedule extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'period_id',
        'type',
        'start_date',
        'end_date',
        'deadline',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'deadline' => 'date',
    ];

    /**
     * Get the period that owns this schedule.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    /**
     * Get all lecturer availabilities for this schedule.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lecturerAvailabilities(): HasMany
    {
        return $this->hasMany(LecturerAvailability::class);
    }

    /**
     * Get all thesis presentations for this schedule.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function thesisPresentations(): HasMany
    {
        return $this->hasMany(ThesisPresentation::class);
    }

    /**
     * Scope to get only proposal hearing schedules.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProposalHearings($query)
    {
        return $query->where('type', 'proposal_hearing');
    }

    /**
     * Scope to get only thesis defense schedules.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeThesisDefenses($query)
    {
        return $query->where('type', 'thesis_defense');
    }

    /**
     * Scope to get schedules ordered chronologically.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('start_date')->orderBy('type');
    }

    /**
     * Check if this schedule has started.
     *
     * @return bool
     */
    public function hasStarted(): bool
    {
        return now()->greaterThanOrEqualTo($this->start_date);
    }

    /**
     * Check if this schedule has ended.
     *
     * @return bool
     */
    public function hasEnded(): bool
    {
        return now()->greaterThan($this->end_date);
    }

    /**
     * Check if this schedule is currently active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->hasStarted() && !$this->hasEnded();
    }

    /**
     * Check if this schedule is upcoming (hasn't started yet).
     *
     * @return bool
     */
    public function isUpcoming(): bool
    {
        return !$this->hasStarted();
    }

    /**
     * Get formatted date range for display.
     *
     * @return string
     */
    public function getDateRangeAttribute(): string
    {
        if ($this->start_date->isSameDay($this->end_date)) {
            return $this->start_date->format('d M Y');
        }

        return $this->start_date->format('d M Y') . ' - ' . $this->end_date->format('d M Y');
    }

    /**
     * Get human-readable type name.
     *
     * @return string
     */
    public function getTypeNameAttribute(): string
    {
        return match ($this->type) {
            'proposal_hearing' => 'Proposal Hearing',
            'thesis_defense' => 'Thesis Defense',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }
}
