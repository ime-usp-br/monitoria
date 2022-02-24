<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SchoolTerm;
use App\Models\Instructor;
use App\Models\ClassSchedule;
use Uspdev\Replicado\DB;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'codtur',
        'tiptur',
        'nomdis',
        'coddis',
        'department',
        'dtainitur',
        'dtafimtur',
        'school_term_id',
    ];

    protected $casts = [
        'dtainitur' => 'datetime',
        'dtafimtur' => 'datetime',
    ];

    public function schoolterm()
    {
        return $this->belongsTo(SchoolTerm::class, "school_term_id");
    }

    public function instructors()
    {
        return $this->belongsToMany(Instructor::class);
    }

    public function classschedules()
    {
        return $this->belongsToMany(ClassSchedule::class);
    }

    public function getGroupsFromReplicado(SchoolTerm $schoolTerm)
    {
        $periodo = [
            '1° Semestre' => '1',
            '2° Semestre' => '2',
        ];
        $codtur = $schoolTerm->year;
        $codtur .= $periodo[$schoolTerm->period] . '%';

        $query = " SELECT T.codtur, T.coddis, D.nomdis, T.dtainitur, T.dtafimtur, T.tiptur";
        $query .= " FROM TURMAGR AS T, DISCIPLINAGR AS D";
        $query .= " WHERE (T.codtur LIKE :codtur)";
        $query .= " AND (T.coddis LIKE :coddis1 OR T.coddis LIKE :coddis2 OR T.coddis LIKE :coddis3 OR T.coddis LIKE :coddis4 )";
        $query .= " AND D.coddis = T.coddis";
        $query .= " AND D.verdis = T.verdis";
        $param = [
            'codtur' => $codtur,
            'coddis1' => 'MAC%',
            'coddis2' => 'MAE%',
            'coddis3' => 'MAP%',
            'coddis4' => 'MAT%',
        ];

        $turmas = array_unique(DB::fetchAll($query, $param), SORT_REGULAR);

        foreach($turmas as $key => $turma){
            $query = " SELECT O.diasmnocp, P.horent, P.horsai";
            $query .= " FROM OCUPTURMA AS O, PERIODOHORARIO AS P";
            $query .= " WHERE O.coddis = :coddis";
            $query .= " AND O.codtur = :codtur";
            $query .= " AND P.codperhor = O.codperhor";
            $param = [
                'coddis' => $turma['coddis'],
                'codtur' => $turma['codtur'],
            ];

            $class_schedules = DB::fetchAll($query, $param);

            $turmas[$key]['class_schedule'] = [];
            foreach($class_schedules as $class_schedule){
                array_push($turmas[$key]['class_schedule'], $class_schedule);
            }

            $query = " SELECT M.codpes, PS.nompes";
            $query .= " FROM OCUPTURMA AS O, MINISTRANTE AS M, PESSOA AS PS";
            $query .= " WHERE O.coddis = :coddis";
            $query .= " AND O.codtur = :codtur";
            $query .= " AND M.coddis = :coddis";
            $query .= " AND M.codtur = :codtur";
            $query .= " AND M.codperhor = O.codperhor";
            $query .= " AND PS.codpes = M.codpes";
            $param = [
                'coddis' => $turma['coddis'],
                'codtur' => $turma['codtur'],
            ];

            $turmas[$key]['instructor'] = array_unique(DB::fetchAll($query, $param), SORT_REGULAR);

            $turmas[$key]['department'] = substr($turmas[$key]['coddis'],0,3);

        }
        return $turmas;
    }
}
