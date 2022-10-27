<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Student;
use App\Models\SchoolTerm;
use Uspdev\Replicado\DB;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'schoolterm_id',
        'nomcur',
        'nomund',
        'sglund',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolterm()
    {
        return $this->belongsTo(SchoolTerm::class);
    }

    public static function getCourseFromReplicado(Student $student, SchoolTerm $st)
    {
        $vinculo = $student->getVinculoFromReplicadoAtSchoolTerm($st);
        if($vinculo=="Graduação"){
            $query = " SELECT CGR.nomcur, UND.nomund, UND.sglund";
            $query .= " FROM PROGRAMAGR AS PGR, HABILPROGGR AS HGR, CURSOGR AS CGR, COLEGIADO AS CLG, UNIDADE AS UND";
            $query .= " WHERE PGR.codpes = :codpes";
            //$query .= " AND (PGR.stapgm = 'A' OR PGR.stapgm = 'R')";
            $query .= " AND HGR.codpes = :codpes";
            $query .= " AND HGR.codpgm = PGR.codpgm";
            $query .= " AND CGR.codcur = HGR.codcur";
            $query .= " AND CLG.codclg = CGR.codclg";
            $query .= " AND (CLG.sglclg = 'CPG' OR CLG.sglclg = 'CG')";
            $query .= " AND UND.codund = CLG.codundrsp";
            $query .= " AND PGR.dtaing = (SELECT MAX(PGR2.dtaing)
                                            FROM PROGRAMAGR AS PGR2 
                                            WHERE PGR2.codpes = :codpes AND DATEDIFF(dd, PGR2.dtaing, convert(datetime, '".$st->started_at."', 103))  > 0)";
            $param = [
                'codpes' => $student->codpes,
            ];
    
            $res = array_unique(DB::fetchAll($query, $param),SORT_REGULAR);
    
            if($res){
                return $res[0];
            }else{
                return null;
            }
        }elseif($vinculo == "PósGraduação"){
            $query = " SELECT AGP.nivpgm, NC.nomcur, UND.nomund, UND.sglund";
            $query .= " FROM AGPROGRAMA AGP, AREA AS A, NOMECURSO as NC, CURSO as C, COLEGIADO AS CLG, UNIDADE AS UND";
            $query .= " WHERE AGP.codpes = :codpes";
            $query .= " AND AGP.dtaselpgm = (SELECT MAX(AGP2.dtaselpgm)
                                            FROM AGPROGRAMA AS AGP2 
                                            WHERE AGP2.codpes = :codpes AND AGP2.dtaselpgm <= convert(datetime, '".$st->started_at."', 103))";
            $query .= " AND A.codare = AGP.codare";
            $query .= " AND NC.codcur = A.codcur";
            $query .= " AND C.codcur = A.codcur";
            $query .= " AND CLG.codclg = C.codclg";
            $query .= " AND CLG.sglclg = 'CPG'";
            $query .= " AND UND.codund = CLG.codundrsp";
            $param = [
                'codpes' => $student->codpes,
            ];
    
            $res = array_unique(DB::fetchAll($query, $param),SORT_REGULAR);
    
            if($res){
                $nomcur = $res[0]["nivpgm"] == "ME" ? "Mestrado em " : ($res[0]["nivpgm"] == "DO" ? "Doutorado em " : ($res[0]["nivpgm"] == "DD" ? "Doutorado Direto em " : ""));
                $nomcur .= $res[0]["nomcur"];
                $res[0]["nomcur"] = $nomcur;
                unset($res[0]["nivpgm"]);

                return $res[0];
            }else{
                return null;
            }
        }
    }
}
