<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Student extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasUuids, HasApiTokens;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'student_number',
        'name',
        'email',
        'password',
        'thesis_title',
        'status',
        'head_division_comment',
        'revision_notes',
        'final_thesis_path',
        'due_date',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'due_date' => 'date',
        'status' => 'integer',
    ];

    public function thesisPresentations()
    {
        return $this->hasMany(ThesisPresentation::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(StudentStatusHistory::class)->orderBy('created_at', 'desc');
    }

    public function supervisionApplications()
    {
        return $this->hasMany(SupervisionApplication::class);
    }

    public function supervisors()
    {
        return $this->belongsToMany(Lecturer::class, 'student_lecturers')
                    ->wherePivotIn('role', [0, 1])
                    ->wherePivot('status', 'active')
                    ->withPivot(['role', 'assignment_date', 'status']);
    }

    public function examiners()
    {
        return $this->belongsToMany(Lecturer::class, 'student_lecturers')
                    ->wherePivot('role', 2)
                    ->wherePivot('status', 'active')
                    ->withPivot(['is_lead_examiner', 'assignment_date', 'status']);
    }

    public function getStatusTextAttribute()
    {
        $statuses = [
            0 => 'New Student',
            1 => 'Thesis Title Submitted',
            2 => 'Thesis Title Declined',
            3 => 'Title Accepted & Forwarded',
            4 => 'Waiting for Schedule',
            5 => 'Scheduled',
            6 => 'Thesis Declined',
            7 => 'Thesis Accepted & Waiting Final',
            8 => 'Completed'
        ];
        
        return $statuses[$this->status] ?? 'Unknown';
    }
}