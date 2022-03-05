<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Student;
use App\Models\SchoolTerm;

class SchoolRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'schoolterm_id',
        'file_path',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolterm()
    {
        return $this->belongsTo(SchoolTerm::class);
    }
}
