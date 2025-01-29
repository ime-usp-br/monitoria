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
use App\Models\Selection;
use Uspdev\Replicado\DB;
use Carbon\Carbon;

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
        'dtainitur' => 'date:d/m/Y',
        'dtafimtur' => 'date:d/m/Y',
    ];

    public function setDtainiturAttribute($value)
    {
        $this->attributes['dtainitur'] = Carbon::createFromFormat('d/m/Y', $value);
    }

    public function setDtafimturAttribute($value)
    {
        $this->attributes['dtafimtur'] = Carbon::createFromFormat('d/m/Y', $value);
    }

    public function getDtainiturAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('d/m/Y') : '';
    }

    public function getDtafimturAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('d/m/Y') : '';
    }

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

    public function selections()
    {
        return $this->hasMany(Selection::class);
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

    public static function whereInOpenSchoolTerm()
    {
        return SchoolClass::whereHas('schoolterm', function($query){
            $query->where(['status'=>'Aberto']);
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

    public function isRequisitionPeriod()
    {
        $start = Carbon::createFromFormat('d/m/Y', $this->schoolterm->start_date_requisitions)->startOfDay();
        $end = Carbon::createFromFormat('d/m/Y', $this->schoolterm->end_date_requisitions)->endOfDay();

        return ($start <= now() and $end >= now());
    }

    public function isInstructor($codpes){
        foreach($this->instructors as $instructor){
            if($instructor->codpes == $codpes){
                return true;
            }
        }
        return false;
    }

    public static function getDisciplinesFromReplicadoBySchoolTermAndInstructor(SchoolTerm $st, Instructor $instructor)
    {
        $query = " SELECT M.coddis";
        $query .= " FROM MINISTRANTE AS M";
        $query .= " WHERE M.codtur LIKE :codtur";
        $query .= " AND M.codpes = :codpes";
        $param = [
            'codpes' => $instructor->codpes,
            'codtur' => $st->year . ($st->period == "1° Semestre" ? "1" : "2") . '%',
        ];

        return array_column(array_unique(DB::fetchAll($query, $param),SORT_REGULAR), "coddis");
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

    public static function getFromReplicadoOldDB(SchoolTerm $st, $instructor_codpes, $coddis)
    {
        $codtur = $st->year . ($st->period == "1° Semestre" ? "1" : "2") . '%';

        $query = " SELECT T.codtur, T.coddis, D.nomdis, T.dtainitur, T.dtafimtur, T.tiptur, DC.pfxdisval";
        $query .= " FROM TURMAGR AS T, DISCIPLINAGR AS D, DISCIPGRCODIGO AS DC";
        $query .= " WHERE (T.coddis = :coddis)";
        $query .= " AND T.codtur LIKE :codtur";
        $query .= " AND T.verdis = (SELECT MAX(T2.verdis) 
                                    FROM TURMAGR AS T2 
                                    WHERE T2.coddis = :coddis AND T2.codtur LIKE :codtur)";
        $query .= " AND D.coddis = T.coddis";
        $query .= " AND D.verdis = T.verdis";
        $query .= " AND DC.coddis = T.coddis";
        $param = [
            'coddis' => $coddis,
            'codtur' => $codtur,
        ];

        $turmas = DB::fetchAll($query, $param);
            
        foreach($turmas as $key => $turma){
            $instructors = Instructor::getFromReplicadoBySchoolClass($turma);
            
            if(in_array($instructor_codpes, array_column($instructors ,"codpes"))){
                $turma['instructors'] = $instructors;
                $turma['class_schedules'] = ClassSchedule::getFromReplicadoBySchoolClass($turma);
                $turma['department_id'] = Department::firstOrCreate(Department::getFromReplicadoByNomabvset($turma['pfxdisval']))->id;
                $turma['school_term_id'] = $st->id;
                $turma['dtainitur'] = Carbon::createFromFormat("Y-m-d H:i:s", $turma["dtainitur"])->format("d/m/Y");
                $turma['dtafimtur'] = Carbon::createFromFormat("Y-m-d H:i:s", $turma["dtafimtur"])->format("d/m/Y");
                unset($turma['pfxdisval']);
                return $turma;
            }
        }

        if($turmas){
            $turmas[0]['codtur'] = $st->year . ($st->period == "1° Semestre" ? "1" : "2") . (max(69, substr(SchoolClass::where("coddis",$coddis)->whereBelongsTo($st)->get()->max("codtur"), -2) ?? 0) + 1);
            $turmas[0]['instructors'] = [Instructor::getFromReplicadoByCodpes($instructor_codpes)];
            $turmas[0]['class_schedules'] = [["diasmnocp" => "sab","horent" => "00:00","horsai" => "00:01"]];
            $turmas[0]['department_id'] = Department::firstOrCreate(Department::getFromReplicadoByNomabvset($turmas[0]['pfxdisval']))->id;
            $turmas[0]['school_term_id'] = $st->id;
            $turmas[0]['dtainitur'] = Carbon::createFromFormat("Y-m-d H:i:s", $turma["dtainitur"])->format("d/m/Y");
            $turmas[0]['dtafimtur'] = Carbon::createFromFormat("Y-m-d H:i:s", $turma["dtafimtur"])->format("d/m/Y");
            unset($turmas[0]['pfxdisval']);
            return $turmas[0];
        }else{
            $query = " SELECT T.coddis, D.nomdis, DC.pfxdisval";
            $query .= " FROM TURMAGR AS T, DISCIPLINAGR AS D, DISCIPGRCODIGO AS DC";
            $query .= " WHERE (T.coddis = :coddis)";
            $query .= " AND T.verdis = (SELECT MAX(T2.verdis) 
                                        FROM TURMAGR AS T2 
                                        WHERE T2.coddis = :coddis)";
            $query .= " AND D.coddis = T.coddis";
            $query .= " AND D.verdis = T.verdis";
            $query .= " AND DC.coddis = T.coddis";
            $param = [
                'coddis' => $coddis,
            ];

            $turmas = DB::fetchAll($query, $param);

            if($turmas){
                $turmas[0]['codtur'] = $st->year . ($st->period == "1° Semestre" ? "1" : "2") . (max(69, substr(SchoolClass::where("coddis",$coddis)->whereBelongsTo($st)->get()->max("codtur"), -2) ?? 0) + 1);
                $turmas[0]['instructors'] = [Instructor::getFromReplicadoByCodpes($instructor_codpes)];
                $turmas[0]['class_schedules'] = [["diasmnocp" => "sab","horent" => "00:00","horsai" => "00:01"]];
                $turmas[0]['department_id'] = Department::firstOrCreate(Department::getFromReplicadoByNomabvset($turmas[0]['pfxdisval']))->id;
                $turmas[0]['school_term_id'] = $st->id;
                $turmas[0]['dtainitur'] = "01/".($st == "1° Semestre" ? "03/" : "08/").$st->year;
                $turmas[0]['dtafimtur'] = "15/".($st == "1° Semestre" ? "07/" : "12/").$st->year;
                unset($turmas[0]['pfxdisval']);
                return $turmas[0];                
            }
        }

        return [];
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
            $query .= " AND T.verdis = (SELECT MAX(T2.verdis) 
                                        FROM TURMAGR AS T2 
                                        WHERE T2.coddis = :coddis AND T2.codtur LIKE :codtur)";
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
                $turmas[$key]['dtainitur'] = Carbon::createFromFormat("Y-m-d H:i:s", $turma["dtainitur"])->format("d/m/Y");
                $turmas[$key]['dtafimtur'] = Carbon::createFromFormat("Y-m-d H:i:s", $turma["dtafimtur"])->format("d/m/Y");
                unset($turmas[$key]['pfxdisval']);

            }
            $schoolclasses = array_merge($schoolclasses, $turmas);
        }
        return $schoolclasses;
    }


    public function calcEstimadedEnrollment()
    {
        $query = " SELECT (T.numins+T.numinscpl+T.numinsopt+T.numinsecr+T.numinsoptlre) AS TOTALINSCRITOS";
        $query .= " FROM TURMAGR AS T";
        $query .= " WHERE (T.coddis = :coddis)";
        $query .= " AND T.codtur LIKE :codtur";
        $query .= " AND T.verdis = (SELECT MAX(T.verdis) 
                                    FROM TURMAGR AS T 
                                    WHERE T.coddis = :coddis)";
        $param = [
            'coddis' => $this->coddis,
            'codtur' => $this->codtur,
        ];

        $res = DB::fetchAll($query, $param);

        return $res ? $res[0]["TOTALINSCRITOS"] : null;
    }
}
