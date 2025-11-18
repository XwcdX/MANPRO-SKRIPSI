<?php

namespace App\Models;

use App\Models\HistoryProposal;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

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
        'thesis_description',
        'status',
        'head_division_comment',
        'revision_notes',
        'final_thesis_path',
        'final_proposal_path',
        'due_date',
        'is_active',
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

    public function initials(): string
    {
        $words = explode(' ', $this->name);
        $initials = mb_substr($words[0], 0, 1);

        if (count($words) > 1) {
            $initials .= mb_substr(end($words), 0, 1);
        }

        return strtoupper($initials);
    }

    public function thesisPresentations()
    {
        return $this->hasMany(ThesisPresentation::class);
    }

    public function proposalPresentations()
    {
        return $this->hasMany(ThesisPresentation::class)
            ->where('presentation_type', 'proposal');
    }

    public function finalPresentations()
    {
        return $this->hasMany(ThesisPresentation::class)
            ->where('presentation_type', 'final');
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
            0 => 'Submit Title',
            1 => 'Choose Supervisor',
            2 => 'Upload Proposal',
            3 => 'Proposal Presentation',
            4 => 'Proposal Final',
            5 => 'Upload Thesis',
            6 => 'Thesis Presentation',
            7 => 'Thesis Final',
        ];

        return $statuses[$this->status] ?? 'Unknown';
    }

    public function activePeriod()
    {
        return $this->periods()->wherePivot('is_active', true)->first();
    }

    public function periods()
    {
        return $this->belongsToMany(Period::class, 'student_periods')
            ->withPivot(['enrollment_date', 'is_active'])
            ->withTimestamps();
    }

    public function history_proposals()
    {
        return $this->hasMany(HistoryProposal::class);
    }

    public function latestProposal()
    {
        return $this->hasOne(HistoryProposal::class)->latestOfMany();
    }
}