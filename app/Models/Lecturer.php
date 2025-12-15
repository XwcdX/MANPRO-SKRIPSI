<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Lecturer extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasUuids, HasRoles, HasApiTokens;

    protected $keyType = 'string';
    public $incrementing = false;
    protected $guard_name = 'lecturer';

    protected $fillable = [
        'name',
        'email',
        'password',
        'primary_division_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    public function initials(): string
    {
        $words = explode(' ', $this->name);
        $initials = mb_substr($words[0], 0, 1);

        if (count($words) > 1) {
            $initials .= mb_substr(end($words), 0, 1);
        }

        return strtoupper($initials);
    }

    public function divisions()
    {
        return $this->belongsToMany(Division::class, 'division_lecturer')->withTimestamps();
    }

    public function primaryDivision()
    {
        return $this->belongsTo(Division::class, 'primary_division_id');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_lecturers')
            ->withPivot(['role', 'is_lead_examiner', 'assignment_date', 'status']);
    }

    public function activeSupervisions()
    {
        return $this->belongsToMany(Student::class, 'student_lecturers')
            ->wherePivotIn('role', [0, 1])
            ->wherePivot('status', 'active');
    }





    public function isAtCapacity(string $periodId)
    {
        $maxStudents = $this->lecturerQuotas()
        ->where('period_id', $periodId)
        ->value('max_students');

        // fallback kalau belum diset
        $maxStudents ??= (int) setting('max_students_per_supervisor', 12);

        return $this->activeSupervisions()->count() >= $maxStudents;
    }

    public function getAvailableCapacity(string $periodId)
    {
        $maxStudents = $this->lecturerQuotas()
        ->where('period_id', $periodId)
        ->value('max_students');

        // fallback kalau belum diset
        $maxStudents ??= (int) setting('max_students_per_supervisor', 12);
        $capacity = $maxStudents - $this->activeSupervisions()->count();
        return $capacity > 0 ? $capacity : 0;
    }

    public function lecturerQuotas()
    {
        return $this->hasMany(LecturerPeriodQuota::class);
    }

    public function topics()
    {
        return $this->hasMany(LecturerTopic::class);
    }

    public function topicApplications()
    {
        return $this->hasMany(TopicApplication::class);
    }

    public function getAvailableCapacityForPeriod($periodId)
    {
        $period = Period::find($periodId);
        if (!$period) return 0;

        $maxStudents = $period->getLecturerQuota($this);
        $currentStudents = $this->activeSupervisions()
            ->whereHas('periods', function($q) use ($periodId) {
                $q->where('periods.id', $periodId);
            })->count();
        
        $capacity = $maxStudents - $currentStudents;
        return $capacity > 0 ? $capacity : 0;
    }

    public function isAtCapacityForPeriod($periodId)
    {
        return $this->getAvailableCapacityForPeriod($periodId) <= 0;
    }
}