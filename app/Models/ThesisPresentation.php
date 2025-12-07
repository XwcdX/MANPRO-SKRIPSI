<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThesisPresentation extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'period_schedule_id',
        'venue_id',
        'student_id',
        'presentation_date',
        'start_time',
        'end_time',
        'presentation_type',
        'notes',
    ];

    protected $casts = [
        'presentation_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function venue()
    {
        return $this->belongsTo(PresentationVenue::class);
    }

    public function periodSchedule()
    {
        return $this->belongsTo(PeriodSchedule::class);
    }

    public function period()
    {
        return $this->hasOneThrough(
            Period::class,
            PeriodSchedule::class,
            'id',
            'id',
            'period_schedule_id',
            'period_id'
        );
    }

    public function examiners()
    {
        return $this->hasMany(PresentationExaminer::class);
    }

    public function leadExaminer()
    {
        return $this->hasOne(PresentationExaminer::class)->where('is_lead_examiner', true);
    }
}
