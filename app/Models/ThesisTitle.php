<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ThesisTitle extends Model
{
    use HasUuids;
    protected $fillable = [
        'title',
        'abstract',
        'completion_year',
        'student_name',
        'student_nrp',
        'document_path',
        "embedding"
    ];

    protected $casts = [
    'embedding' => 'array',
];
}
