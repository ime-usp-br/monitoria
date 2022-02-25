<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Group;
use Uspdev\Replicado\DB;

class ClassSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'diasmnocp',
        'horent',
        'horsai',
    ];

    public function groups()
    {
        return $this->belongsToMany(Group::class);
    }

    public static function getFromReplicadoByGroup($group){
        $query = " SELECT O.diasmnocp, P.horent, P.horsai";
        $query .= " FROM OCUPTURMA AS O, PERIODOHORARIO AS P";
        $query .= " WHERE O.coddis = :coddis";
        $query .= " AND O.codtur = :codtur";
        $query .= " AND P.codperhor = O.codperhor";
        $param = [
            'coddis' => $group['coddis'],
            'codtur' => $group['codtur'],
        ];

        return DB::fetchAll($query, $param);
    }
}
