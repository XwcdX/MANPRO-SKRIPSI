<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentStatusHistory extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;
    const UPDATED_AT = null;
    
    protected $fillable = [
        'student_id',
        'previous_status',
        'new_status',
        'changed_by',
        'reason',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function changer()
    {
        return $this->belongsTo(Lecturer::class, 'changed_by');
    }
}
