<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use \Spatie\Permission\Traits\HasRoles;
use \Uspdev\SenhaunicaSocialite\Traits\HasSenhaunica;
use Uspdev\Replicado\DB;

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

    protected static $syncingVinculo = false;

    public static function booted(){

        static::saved(function ($user){
            if (static::$syncingVinculo) {
                return;
            }
            static::$syncingVinculo = true;
            try {
                $codpes = (string) $user->codpes;
                if (str_contains((string) env('LOG_AS_ADMINISTRATOR'), $codpes) && !$user->hasRole('Administrador')) {
                    $user->assignRole('Administrador');
                }
                $user->syncVinculoRoles();
            } finally {
                static::$syncingVinculo = false;
            }
        });
    }

    public function syncVinculoRoles(): void
    {
        if (!$this->codpes) {
            return;
        }

        $target = $this->getVinculosFromReplicadoByCodpes($this->codpes);

        foreach (['Aluno', 'Docente'] as $role) {
            $shouldHave = in_array($role, $target, true);
            $hasRole = $this->hasRole($role);

            if ($shouldHave && !$hasRole) {
                $this->assignRole($role);
            } elseif (!$shouldHave && $hasRole) {
                $this->removeRole($role);
            }
        }
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
                if( str_contains($r['tipvin'], 'ALUNOGR') || str_contains($r['tipvin'], 'ALUNOPOS') || str_contains($r['tipvin'], 'ALUNOPOSESP')){
                    array_push($vinculos, 'Aluno');
                }elseif(str_contains($r['tipvin'], 'SERVIDOR')){
                    if($r['tipfnc'] == 'Docente'){
                        array_push($vinculos, 'Docente');
                    }
                }
            }
        }

        return $vinculos;
    }
}
