<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Enrollment;
use App\Models\Requisition;
use App\Models\SelfEvaluation;
use App\Models\SchoolTerm;
use Carbon\Carbon;


class Selection extends Model
{
    use HasFactory;
    use \Znck\Eloquent\Traits\BelongsToThrough;

    protected $fillable = [
        'student_id',
        'school_class_id',
        'enrollment_id',
        'requisition_id',
        'selecionado_sem_inscricao',
        'codpescad',# Código da Pessoal que Cadastrou
        'dtafimvin',# Data Fim Vinculo
        'sitatl',# Situação Atual - Ativo, Concluido e Desligado 
        'motdes',# Motivo do Desligamento
    ];

    protected $casts = [
        'dtafimvin' => 'date:d/m/Y',
    ];

    public function setDtafimvinAttribute($value)
    {
        $this->attributes['dtafimvin'] = Carbon::createFromFormat('d/m/Y', $value);
    }

    public function getDtafimvinAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('d/m/Y') : '';
    }

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

    public function selfevaluation()
    {
        return $this->hasOne(SelfEvaluation::class);
    }

    public function instructorevaluation()
    {
        return $this->hasOne(InstructorEvaluation::class);
    }

    public function schoolterm()
    {
        return $this->belongsToThrough(SchoolTerm::class, SchoolClass::class);
    }
}