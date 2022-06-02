<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Student;
use App\Models\SchoolClass;

class Frequency extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_class_id',
        'student_id',
        'month',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, "student_id");
    }

    public function schoolclass()
    {
        return $this->belongsTo(SchoolClass::class, "school_class_id");
    }
}
