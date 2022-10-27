<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Uspdev\Replicado\DB;
use App\Models\Enrollment;
use App\Models\SchoolRecord;
use App\Models\User;
use App\Models\Selection;
use App\Models\Recommendation;
use App\Models\Frequency;
use App\Models\Course;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'codpes',
        'nompes',
        'codema',
    ];

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function schoolrecords()
    {
        return $this->hasMany(SchoolRecord::class);
    }

    public function selections()
    {
        return $this->hasMany(Selection::class);
    }

    public function recommendations()
    {
        return $this->hasMany(Recommendation::class);
    }

    public function frequencies()
    {
        return $this->hasMany(Frequency::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function getNomAbrev()
    {
        $pattern = '/ de | do | dos | da | das | e /i';
        $nome = preg_replace($pattern,' ',$this->nompes);
        $nome = explode(' ', $nome);
        
        $nomes_meio = ' ';
        
        if(count($nome) > 2){
            for($x=1;$x<count($nome)-1;$x++){
                $nomes_meio .= $nome[$x][0].". ";
            }
        }
        
        $nomeabreviado = array_shift($nome).$nomes_meio.array_pop($nome);
        
        return $nomeabreviado;
    }

    public function hasSelectionInOpenSchoolTerm()
    {
        return $this->getSelectionFromOpenSchoolTerm() ? 1 : 0;
    }

    public function getSelectionFromOpenSchoolTerm()
    {
        return $this->selections()->whereHas('schoolclass', function ($query){
            return $query->whereHas('schoolterm', function ($query){
                return $query->where(['status'=>'Aberto']);
            });
        })->first();
    }

    public function getSchoolRecordFromOpenSchoolTerm()
    {
        $record = $this->schoolrecords()->whereHas('schoolterm', function($query){
                        $query->where('status','=', 'Aberto');
                    })->first();
        return $record ? $record->file_path : "";
    }

    public function hasEnrollmentInEnrollmentPeriod()
    {
        return $this->enrollments()->wherehas('schoolclass', function ($q) {
                    return $q->whereHas('schoolterm', function($q){ 
                        return $q->where('start_date_enrollments', '<=', now())->where('end_date_enrollments', '>=', now());
                    });
                })->first() ? 1 : 0;
    }

    public function getEnrollmentsInEnrollmentPeriod()
    {
        return $this->enrollments()->wherehas('schoolclass', function ($q) {
                    return $q->whereHas('schoolterm', function($q){ 
                        return $q->where('start_date_enrollments', '<=', now())->where('end_date_enrollments', '>=', now());
                    });
                })->get();
    }
    
    public static function getFromReplicadoByCodpes($codpes)
    {
        $query = " SELECT P.codpes, P.nompes, EP.codema";
        $query .= " FROM PESSOA AS P, EMAILPESSOA as EP";
        $query .= " WHERE P.codpes = :codpes";
        $query .= " AND EP.codpes = :codpes";
        $query .= " AND EP.stamtr = :stamtr";
        $param = [
            'codpes' => $codpes,
            'stamtr' => 'S'
        ];

        $res = array_unique(DB::fetchAll($query, $param),SORT_REGULAR);


        if(!$res){
            $query = " SELECT P.codpes, P.nompes, EP.codema";
            $query .= " FROM PESSOA AS P, EMAILPESSOA as EP";
            $query .= " WHERE P.codpes = :codpes";
            $query .= " AND EP.codpes = :codpes";
            $param = [
                'codpes' => $codpes,
            ];

            $res = array_unique(DB::fetchAll($query, $param),SORT_REGULAR);
        }

        if($res){
            return $res[0];
        }else{
            return [];
        }
    }
    
    public static function getFromReplicadoByNompes($nompes)
    {
        $query = " SELECT P.codpes, P.nompes, EP.codema";
        $query .= " FROM PESSOA AS P, EMAILPESSOA as EP";
        $query .= " WHERE P.nompes LIKE :nompes";
        $query .= " AND EP.codpes = P.codpes";
        $query .= " AND EP.stamtr = :stamtr";
        $param = [
            'nompes' => "%".$nompes."%",
            'stamtr' => 'S'
        ];

        $res = array_unique(DB::fetchAll($query, $param),SORT_REGULAR);

        foreach($res as $key=>$pessoa){
            if(!in_array('Aluno', User::getVinculosFromReplicadoByCodpes($pessoa['codpes']))){
                unset($res[$key]);
            }
        }

        return array_values($res);
    }

    public function getTelefonesFromReplicado()
    {
        $query = " SELECT TP.codddi, TP.codddd, TP.numtel, TP.tiptelpes";
        $query .= " FROM TELEFPESSOA AS TP";
        $query .= " WHERE TP.codpes=:codpes";
        $query .= " AND (TP.tiptelpes=:residencial OR TP.tiptelpes=:celular)";
        $param = [
            "codpes"=>$this->codpes,
            "residencial"=>"residencial",
            "celular"=>"celular"
        ];

        $res = array_unique(DB::fetchAll($query, $param),SORT_REGULAR);

        return $res;
    }

    public function getSexo()
    {
        $query = " SELECT P.sexpes";
        $query .= " FROM PESSOA AS P";
        $query .= " WHERE P.codpes = :codpes";
        $param = [
            'codpes' => $this->codpes,
        ];

        $res = DB::fetchAll($query, $param)[0];

        return $res['sexpes'];
    }

    // Função usada para pegar o vinculo em determinado periodo, util para dados importados do sistema antigo 
    public function getVinculoFromReplicadoAtSchoolTerm(SchoolTerm $st)
    {
        $query = " SELECT VP.tipvin";
        $query .= " FROM VINCULOPESSOAUSP AS VP";
        $query .= " WHERE VP.codpes = :codpes";
        $query .= " AND VP.dtainivin <= convert(datetime, '".$st->started_at."', 103)";
        $query .= " AND (VP.dtafimvin IS NULL OR VP.dtafimvin >= convert(datetime, '".$st->started_at."', 103))";
        $param = [
            'codpes' => $this->codpes,
        ];

        $res = array_unique(DB::fetchAll($query, $param),SORT_REGULAR);

        $tipvin =  array_column($res, "tipvin");

        if(in_array("ALUNOPOS", $tipvin)){
            return "PósGraduação";
        }elseif(in_array("ALUNOGR", $tipvin)){
            return "Graduação";
        }else{        
            return null; 
        }
    }
}
