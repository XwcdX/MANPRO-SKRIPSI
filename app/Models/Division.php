<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'description',
    ];

    public function lecturers()
    {
        return $this->belongsToMany(Lecturer::class, 'division_lecturer')->withTimestamps();
    }

    public function headOfDivision()
    {
        return $this->hasOne(Lecturer::class, 'primary_division_id');
    }
}