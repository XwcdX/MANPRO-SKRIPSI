<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupervisionApplication extends Model
{
    use HasFactory, HasUuids;
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'student_id',
        'lecturer_id',
        'period_id',
        'division_id',
        'proposed_role',
        'student_notes',
        'lecturer_notes',
        'status',
    ];

    protected $casts = [
        'proposed_role' => 'integer',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function period()
    {
        return $this->belongsTo(Period::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }
}
