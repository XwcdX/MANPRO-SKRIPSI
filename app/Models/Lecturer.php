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
        'title',
        'division_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'title' => 'integer',
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

    public function division()
    {
        return $this->belongsTo(Division::class);
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

    public function schedules()
    {
        return $this->belongsToMany(Schedule::class, 'lecturer_schedules')
            ->withPivot(['status', 'notes']);
    }

    public function getTitleTextAttribute()
    {
        $titles = [
            0 => 'Supervisor 2 Only',
            1 => 'Can be Supervisor 1',
            2 => 'Head of Division',
            3 => 'Head of Thesis Department'
        ];

        return $titles[$this->title] ?? 'Unknown';
    }

    public function canSupervise()
    {
        return in_array($this->title, [0, 1]);
    }

    public function canBeLeadSupervisor()
    {
        return $this->title >= 1;
    }

    public function isAtCapacity()
    {
        $maxStudents = (int) setting('max_students_per_supervisor', 12);
        return $this->activeSupervisions()->count() >= $maxStudents;
    }

    public function getAvailableCapacity()
    {
        $maxStudents = (int) setting('max_students_per_supervisor', 12);
        $capacity = $maxStudents - $this->activeSupervisions()->count();
        return $capacity > 0 ? $capacity : 0;
    }

    public function lecturerQuotas()
    {
        return $this->hasMany(LecturerPeriodQuota::class);
    }
}