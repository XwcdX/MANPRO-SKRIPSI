<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'date',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function thesisPresentations()
    {
        return $this->hasMany(ThesisPresentation::class);
    }

    public function lecturers()
    {
        return $this->belongsToMany(Lecturer::class, 'lecturer_schedules')
            ->withPivot(['status', 'notes']);
    }
}
