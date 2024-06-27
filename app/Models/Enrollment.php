<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Selection;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_class_id',
        'student_id',
        'voluntario',
        'disponibilidade_diurno',
        'disponibilidade_noturno',
        'preferencia_horario',
        'observacoes',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function schoolclass()
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function selection()
    {
        return $this->hasOne(Selection::class);
    }

    public function others_scholarships()
    {
        return $this->morphToMany(
            Scholarship::class,
            'model',
            'model_has_scholarships',
            'model_id',
            'scholarship_id',
        );
    }

    public function hasOtherSelectionInOpenSchoolTerm()
    {
        $schoolclass_id = $this->school_class_id;
        return $this->student->selections()->whereHas('schoolclass', function ($query) use ($schoolclass_id){
            return $query->where('id', '!=', $schoolclass_id)
                         ->whereHas('schoolterm', function($query){
                            $query->where(['status'=>'Aberto']);
                        });
        })->first() ? 1 : 0;
    }
}
