<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Period extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'registration_end',
        'default_quota',
        'archived_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_end' => 'date',
        'default_quota' => 'integer',
        'archived_at' => 'datetime',
    ];

    public function getStatusAttribute(): string
    {
        if ($this->archived_at) {
            return 'archived';
        }

        $now = now();

        if ($now->lt($this->start_date)) {
            return 'upcoming';
        }

        if ($now->between($this->start_date, $this->registration_end)) {
            return 'registration_open';
        }

        if ($now->between($this->registration_end, $this->end_date)) {
            return 'in_progress';
        }

        return 'completed';
    }

    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, ['registration_open', 'in_progress']) && !$this->archived_at;
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

    public function isRegistrationOpen(): bool
    {
        return $this->status === 'registration_open';
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

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
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