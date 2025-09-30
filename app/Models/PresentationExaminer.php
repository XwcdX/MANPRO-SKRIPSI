<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresentationExaminer extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'thesis_presentation_id',
        'lecturer_id',
        'is_lead_examiner',
        'attendance_status',
        'evaluation_score',
        'comments',
    ];

    protected $casts = [
        'is_lead_examiner' => 'boolean',
        'evaluation_score' => 'decimal:2',
    ];

    public function thesisPresentation()
    {
        return $this->belongsTo(ThesisPresentation::class);
    }

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class);
    }
}
