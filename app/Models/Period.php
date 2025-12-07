<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Period extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'registration_end',
        'proposal_schedule_start_time',
        'proposal_schedule_end_time',
        'thesis_schedule_start_time',
        'thesis_schedule_end_time',
        'break_start_time',
        'break_end_time',
        'proposal_slot_duration',
        'thesis_slot_duration',
        'default_quota',
        'archived_at',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'registration_end' => 'datetime',
        'default_quota' => 'integer',
        'proposal_slot_duration' => 'integer',
        'thesis_slot_duration' => 'integer',
        'archived_at' => 'datetime',
    ];

    /**
     * Get all schedules for this period.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(PeriodSchedule::class);
    }

    /**
     * Get only proposal hearing schedules.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function proposalHearings(): HasMany
    {
        return $this->hasMany(PeriodSchedule::class)->where('type', 'proposal_hearing');
    }

    /**
     * Get only thesis defense schedules.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function thesisDefenses(): HasMany
    {
        return $this->hasMany(PeriodSchedule::class)->where('type', 'thesis_defense');
    }

    /**
     * Check if registration is open.
     * Registration is open as long as there's at least one proposal hearing that hasn't started.
     *
     * @return bool
     */
    public function isRegistrationOpen(): bool
    {
        if ($this->archived_at) {
            return false;
        }

        $now = now();

        // Check if we're past registration_end date
        if ($this->registration_end && $now->gt($this->registration_end)) {
            return false;
        }

        // Check if there's any proposal hearing that hasn't started yet
        $upcomingProposalHearing = $this->schedules()
            ->where('type', 'proposal_hearing')
            ->where('start_date', '>', $now)
            ->exists();

        return $upcomingProposalHearing;
    }

    public function getStatusAttribute(): string
    {
        if ($this->archived_at) {
            return 'archived';
        }

        $now = now();
        
        if (!$this->start_date) {
            return 'upcoming';
        }
        
        if ($now->lt($this->start_date)) {
            return 'upcoming';
        }

        if ($this->isRegistrationOpen()) {
            return 'registration_open';
        }

        // Check if any proposal hearing is active
        $activeProposalHearing = $this->schedules()
            ->where('type', 'proposal_hearing')
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->exists();

        if ($activeProposalHearing) {
            return 'proposal_hearing';
        }

        // Check if any thesis defense is active
        $activeThesisDefense = $this->schedules()
            ->where('type', 'thesis_defense')
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->exists();

        if ($activeThesisDefense) {
            return 'thesis';
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return 'completed';
        }

        return 'proposal_in_progress';
    }

    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, [
            'registration_open',
            'proposal_in_progress',
            'proposal_hearing',
            'thesis_in_progress',
            'thesis'
        ]) && !$this->archived_at;
    }

    public static function active()
    {
        return static::whereNull('archived_at')
            ->where(function($query) {
                $now = now();
                $query->where(function($q) use ($now) {
                    $q->where('start_date', '<=', $now)
                      ->where('end_date', '>=', $now);
                });
            })
            ->first();
    }



    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function archive(): void
    {
        $this->archived_at = now();
        $this->save();
    }

    public function getLecturerQuota(Lecturer $lecturer): int
    {
        $customQuota = LecturerPeriodQuota::where('lecturer_id', $lecturer->id)
            ->where('period_id', $this->id)
            ->first();

        return $customQuota ? $customQuota->max_students : $this->default_quota;
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_periods')
            ->withPivot(['enrollment_date', 'is_active'])
            ->withTimestamps();
    }



    public function supervisionApplications()
    {
        return $this->hasMany(SupervisionApplication::class);
    }

    public function lecturerTopics()
    {
        return $this->hasMany(LecturerTopic::class);
    }

    public function lecturerQuotas()
    {
        return $this->hasMany(LecturerPeriodQuota::class);
    }

    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }

    public function statusHistories()
    {
        return $this->hasMany(StudentStatusHistory::class);
    }
}