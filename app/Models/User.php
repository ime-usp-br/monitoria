<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use \Spatie\Permission\Traits\HasRoles;
use \Uspdev\SenhaunicaSocialite\Traits\HasSenhaunica;
use Uspdev\Replicado\Pessoa;
use Uspdev\Replicado\DB;
use App\Models\Instructor;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, HasSenhaunica;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public static function booted(){

        static::created(function ($user){
            $codpes = $user->codpes;
            if (str_contains(env('LOG_AS_ADMINISTRATOR'), $codpes)){
                $user->assignRole("Administrador");
            }
            foreach($user->getVinculosFromReplicadoByCodpes($codpes) as $vinculo){
                if ($vinculo == 'Docente'){
                    $user->assignRole("Docente");
                }
                if ($vinculo == 'Aluno'){
                    $user->assignRole("Aluno");
                }
            }
        });
    }

    public static function getVinculosFromReplicadoByCodpes($codpes)
    {
        $query = " SELECT VP.tipvin, VP.dtafimvin, VP.tipfnc";
        $query .= " FROM VINCULOPESSOAUSP AS VP";
        $query .= " WHERE VP.codpes = :codpes";
        $param = [
            'codpes' => $codpes,
        ];

        $res = array_unique(DB::fetchAll($query, $param),SORT_REGULAR);
        
        $vinculos = [];
        foreach($res as $r){
            if(!$r['dtafimvin']){
                if($r['tipvin'] == 'ALUNOGR' || $r['tipvin'] == 'ALUNOPOS'){
                    array_push($vinculos, 'Aluno');
                }elseif($r['tipvin'] == 'SERVIDOR'){
                    if($r['tipfnc'] == 'Docente'){
                        array_push($vinculos, 'Docente');
                    }
                }
            }
        }

        return $vinculos;
    }
}
