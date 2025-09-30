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
        'registration_start',
        'registration_end',
        'supervision_selection_deadline',
        'title_submission_deadline',
        'is_active',
        'status',
        'max_students',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_start' => 'date',
        'registration_end' => 'date',
        'supervision_selection_deadline' => 'date',
        'title_submission_deadline' => 'date',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($period) {
            if ($period->is_active) {
                static::where('id', '!=', $period->id)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }
        });
    }

    public static function active()
    {
        return static::where('is_active', true)->first();
    }

    public function isRegistrationOpen(): bool
    {
        $now = now();
        return $this->is_active
            && $this->status === 'registration_open'
            && $now->between($this->registration_start, $this->registration_end);
    }

    public function isInProgress(): bool
    {
        $now = now();
        return $this->is_active
            && $now->between($this->start_date, $this->end_date);
    }

    public function canSubmitTitle(): bool
    {
        $now = now();
        return $this->is_active
            && $this->status === 'in_progress'
            && (!$this->title_submission_deadline || $now->lte($this->title_submission_deadline));
    }

    public function canSelectSupervisor(): bool
    {
        $now = now();
        return $this->is_active
            && $now->between($this->registration_start, $this->supervision_selection_deadline ?? $this->end_date);
    }

    public function hasReachedMaxStudents(): bool
    {
        if (!$this->max_students) {
            return false;
        }
        return $this->students()->wherePivot('is_active', true)->count() >= $this->max_students;
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

    public function statusHistories()
    {
        return $this->hasMany(StudentStatusHistory::class);
    }
}