<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Instructor;
use App\Models\SchoolClass;
use Uspdev\Replicado\DB;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'codset',
        'nomabvset',
        'nomset',
        'sglund',
        'nomund',
    ];

    public function instructors(){
        return $this->hasMany(Instructor::class);
    }

    public function schoolclasses(){
        return $this->hasMany(SchoolClass::class);
    }

    public static function getFromReplicadoByInstitute($sglund){
        $query = " SELECT S.codset, S.nomabvset, S.nomset, U.sglund, U.nomund";
        $query .= " FROM SETOR AS S, UNIDADE AS U";
        $query .= " WHERE U.sglund = :sglund";
        $query .= " AND S.codund = U.codund";
        $query .= " AND S.tipset = :tipset";
        $param = [
            'sglund' => $sglund,
            'tipset' => 'Departamento de Ensino',
        ];

        return DB::fetchAll($query, $param);
    }

    public static function getFromReplicadoByNomabvset($nomabvset){
        $query = " SELECT S.codset, S.nomabvset, S.nomset, U.sglund, U.nomund";
        $query .= " FROM SETOR AS S, UNIDADE AS U";
        $query .= " WHERE S.nomabvset = :nomabvset";
        $query .= " AND U.codund = S.codund";
        $param = [
            'nomabvset' => $nomabvset,
        ];

        return DB::fetchAll($query, $param)[0];
    }

    public static function getFromReplicadoByCodset($codset){

        $query = " SELECT S.codset, S.nomabvset, S.nomset, U.sglund, U.nomund";
        $query .= " FROM SETOR AS S, UNIDADE AS U";
        $query .= " WHERE S.codset = :codset";
        $query .= " AND U.codund = S.codund";
        $param = [
            'codset' => $codset,
        ];

        return DB::fetchAll($query, $param)[0];
    }
}
