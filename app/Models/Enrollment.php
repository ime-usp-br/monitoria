<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Student;
use App\Models\Group;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'student_id',
        'voluntario',
        'disponibilidade_diurno',
        'disponibilidade_noturno',
        'preferencia_horario',
        'observações',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }
}
