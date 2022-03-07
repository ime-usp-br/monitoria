<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SchoolTerm;
use App\Models\Instructor;
use App\Models\ClassSchedule;
use App\Models\Department;
use App\Models\Requisition;
use App\Models\Enrollment;
use Uspdev\Replicado\DB;

class SchoolClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'codtur',
        'tiptur',
        'nomdis',
        'coddis',
        'dtainitur',
        'dtafimtur',
        'school_term_id',
        'department_id',
    ];

    protected $casts = [
        'dtainitur' => 'datetime',
        'dtafimtur' => 'datetime',
    ];

    public function schoolterm()
    {
        return $this->belongsTo(SchoolTerm::class, "school_term_id");
    }

    public function department()
    {
        return $this->belongsTo(Department::class, "department_id");
    }

    public function instructors()
    {
        return $this->belongsToMany(Instructor::class);
    }

    public function classschedules()
    {
        return $this->belongsToMany(ClassSchedule::class);
    }

    public function requisition(){
        return $this->hasOne(Requisition::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public static function whereInEnrollmentPeriod()
    {
        return SchoolClass::whereHas('schoolterm', function($query){
            $query->where('start_date_enrollments', '<=', now())
            ->where('end_date_enrollments', '>=', now());
        });
    }

    public static function whereInRequisitionPeriod()
    {
        return SchoolClass::whereHas('schoolterm', function($query){
            $query->where('start_date_requisitions', '<=', now())
            ->where('end_date_requisitions', '>=', now());
        });
    }

    public function getEnrollmentByStudent(Student $student)
    {
        return $this->enrollments()->where(['student_id'=>$student->id])->first();
    }

    public function isStudentEnrolled(Student $student)
    {
        if($this->enrollments){
            foreach($this->enrollments as $enrollment){
                if($enrollment->student == $student){
                    return true;
                }
            }
        }
        return false;
    }

    public function isSchoolTermOpen(){
        if($this->schoolterm->status == "Aberto"){
            return true;
        }else{
            return false;
        }
    }

    public function isInstructor($codpes){
        foreach($this->instructors as $instructor){
            if($instructor->codpes == $codpes){
                return true;
            }
        }
        return false;
    }

    public static function getDisciplinesFromReplicadoByInstitute($sglund){
        $query = " SELECT DC.coddis";
        $query .= " FROM UNIDADE AS U, SETOR AS S, PREFIXODISCIP AS PD, DISCIPGRCODIGO AS DC";
        $query .= " WHERE (U.sglund LIKE :sglund)";
        $query .= " AND S.codund = U.codund";
        $query .= " AND PD.codset = S.codset";
        $query .= " AND DC.codclg = PD.codclg";
        $param = [
            'sglund' => $sglund,
        ];

        return array_unique(DB::fetchAll($query, $param),SORT_REGULAR);

    }

    public static function getFromReplicadoBySchoolTerm(SchoolTerm $schoolTerm)
    {
        $disciplinas = SELF::getDisciplinesFromReplicadoByInstitute(env("UNIDADE"));

        $periodo = [
            '1° Semestre' => '1',
            '2° Semestre' => '2',
        ];
        $schoolclasses = [];
        foreach($disciplinas as $disc){
            $codtur = $schoolTerm->year;
            $codtur .= $periodo[$schoolTerm->period] . '%';
            $coddis = $disc['coddis'];


            $query = " SELECT T.codtur, T.coddis, D.nomdis, T.dtainitur, T.dtafimtur, T.tiptur, DC.pfxdisval";
            $query .= " FROM TURMAGR AS T, DISCIPLINAGR AS D, DISCIPGRCODIGO AS DC";
            $query .= " WHERE (T.coddis = :coddis)";
            $query .= " AND T.codtur LIKE :codtur";
            $query .= " AND T.verdis = (SELECT MAX(T.verdis) 
                                        FROM TURMAGR AS T 
                                        WHERE T.coddis = :coddis)";
            $query .= " AND D.coddis = T.coddis";
            $query .= " AND D.verdis = T.verdis";
            $query .= " AND DC.coddis = T.coddis";
            $param = [
                'coddis' => $coddis,
                'codtur' => $codtur,
            ];

            $turmas = DB::fetchAll($query, $param);
            
            foreach($turmas as $key => $turma){
                $turmas[$key]['class_schedules'] = ClassSchedule::getFromReplicadoBySchoolClass($turma);
                $turmas[$key]['instructors'] = Instructor::getFromReplicadoBySchoolClass($turma);
                $turmas[$key]['department_id'] = Department::firstOrCreate(Department::getFromReplicadoByNomabvset($turma['pfxdisval']))->id;
                $turmas[$key]['school_term_id'] = $schoolTerm->id;
                unset($turmas[$key]['pfxdisval']);

            }
            $schoolclasses = array_merge($schoolclasses, $turmas);
        }
        return $schoolclasses;
    }
}
