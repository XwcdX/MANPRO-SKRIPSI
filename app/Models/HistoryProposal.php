<?php

namespace App\Models;

use App\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class HistoryProposal extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'student_id',
        'division_id',
        'description',
        'file_path',
        'comment',
        'status',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }
}
