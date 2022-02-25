<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Group;
use App\Models\Department;
use Uspdev\Replicado\DB;

class Instructor extends Model
{
    use HasFactory;

    protected $fillable = [
        'codpes',
        'nompes',
        'department_id'
    ];

    public function groups()
    {
        return $this->belongsToMany(Group::class);
    }

    public function department(){
        return $this->belongsTo(Department::class, "department_id");
    }

    public static function getFromReplicadoByGroup($group){
        $query = " SELECT M.codpes";
        $query .= " FROM OCUPTURMA AS O, MINISTRANTE AS M";
        $query .= " WHERE O.coddis = :coddis";
        $query .= " AND O.codtur = :codtur";
        $query .= " AND M.coddis = :coddis";
        $query .= " AND M.codtur = :codtur";
        $query .= " AND M.codperhor = O.codperhor";
        $param = [
            'coddis' => $group['coddis'],
            'codtur' => $group['codtur'],
        ];

        $res = array_unique(DB::fetchAll($query, $param),SORT_REGULAR);

        $instructors = [];
        foreach($res as $codpes){
            array_push($instructors, SELF::getFromReplicadoByCodpes($codpes['codpes']));
        }

        return $instructors;
    }

    public static function getFromReplicadoByCodpes($codpes){
        $query = " SELECT P.codpes, P.nompes, VP.codset";
        $query .= " FROM VINCULOPESSOAUSP AS VP, PESSOA AS P";
        $query .= " WHERE VP.codpes = :codpes";
        $query .= " AND VP.tipfnc = :tipfnc";
        $query .= " AND P.codpes = :codpes";
        $param = [
            'codpes' => $codpes,
            'tipfnc' => 'Docente',
        ];

        $res = DB::fetchAll($query, $param);
        
        $res[0]["department_id"] = Department::firstOrCreate(Department::getFromReplicadoByCodset($res[0]["codset"]))->id;
        unset($res[0]["codset"]);

        return $res[0];
    }
}
