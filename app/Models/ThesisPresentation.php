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
        'venue_id',
        'schedule_id',
        'student_id',
        'presentation_type',
        'notes',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function venue()
    {
        return $this->belongsTo(PresentationVenue::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function examiners()
    {
        return $this->hasMany(PresentationExaminer::class);
    }
}
