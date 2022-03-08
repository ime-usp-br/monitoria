<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Enrollment;
use App\Models\Requisition;


class Selection extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'school_class_id',
        'enrollment_id',
        'requisition_id',
        'selecionado_sem_inscricao',
        'codpescad',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolclass()
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function requisition()
    {
        return $this->belongsTo(Requisition::class);
    }
}