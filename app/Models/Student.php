<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Uspdev\Replicado\DB;
use App\Models\Enrollment;
use App\Models\SchoolRecord;
use App\Models\User;

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

    public function getSchoolRecordFromOpenSchoolTerm()
    {
        return $this->schoolrecords()->whereHas('schoolterm', function($query){
                        $query->where('status','=', 'Aberto');
                    })->first();
    }
    
    public static function getFromReplicadoByCodpes($codpes)
    {
        if(in_array('Aluno', User::getVinculosFromReplicadoByCodpes($codpes))){
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
}
