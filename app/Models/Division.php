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
        return $this->hasMany(Lecturer::class);
    }

    public function headOfDivision()
    {
        return $this->hasOne(Lecturer::class)->where('title', 2);
    }
}