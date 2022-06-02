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
        return $this->schoolrecords()->whereHas('schoolterm', function($query){
                        $query->where('status','=', 'Aberto');
                    })->first();
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
