<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TopicApplication extends Model
{
    use HasUuids;

    protected $fillable = [
        'student_id',
        'topic_id',
        'lecturer_id',
        'period_id',
        'student_notes',
        'lecturer_notes',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function topic()
    {
        return $this->belongsTo(LecturerTopic::class, 'topic_id');
    }

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function period()
    {
        return $this->belongsTo(Period::class);
    }
}
