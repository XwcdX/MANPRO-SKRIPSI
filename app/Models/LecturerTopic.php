<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LecturerTopic extends Model
{
    use HasUuids;

    protected $fillable = [
        'lecturer_id',
        'period_id',
        'topic',
        'description',
        'student_quota',
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'student_quota' => 'integer',
    ];

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function period()
    {
        return $this->belongsTo(Period::class);
    }

    public function applications()
    {
        return $this->hasMany(TopicApplication::class, 'topic_id');
    }

    public function acceptedApplications()
    {
        return $this->hasMany(TopicApplication::class, 'topic_id')->where('status', 'accepted');
    }
}
