<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Uspdev\Replicado\DB;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'codpes',
        'nompes',
        'codema',
        'sexo',
        'cpf',
        'endereco',
        'complemento',
        'cep',
        'bairro',
        'cidade',
        'estado',
        'tel_celular',
        'tel_residencial',
        'possui_conta_bb',
    ];

    public static function getFromReplicadoByCodpes($codpes)
    {
        $query = " SELECT P.nompes, P.codpes, P.sexpes, P.tipdocidf, 
                          P.numdocidf, P.numcpf, EP.epflgr, EP.cpllgr, 
                          EP.nombro, EP.codendptl, L.cidloc, L.sglest, 
                          TP.tiptelpes, TP.codddi, TP.codddd, TP.numtel, TL.nomtiplgr, EP.numlgr";
        $query .= " FROM PESSOA as P, ENDPESSOA as EP, LOCALIDADE as L, TELEFPESSOA as TP, TIPOLOGRADOURO as TL";
        $query .= " WHERE (P.codpes = :codpes)";
        $query .= " AND EP.codpes = :codpes";
        $query .= " AND L.codloc = EP.codloc";
        $query .= " AND TP.codpes = :codpes";
        $query .= " AND TL.codtiplgr = EP.codtiplgr";
        $param = [
            'codpes' => $codpes,
        ];

        $res = array_unique(DB::fetchAll($query, $param),SORT_REGULAR);
        
        return $res;
    }
}
