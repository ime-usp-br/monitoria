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
                $user->givePermissionTo('admin');
            }
            foreach(Pessoa::vinculos($codpes) as $vinculo){
                if (str_contains($vinculo, 'Docente') && str_contains($vinculo, 'IME')){
                    $user->assignRole("Docente");
                }
                if (str_contains($vinculo, 'Aluno de Graduação')){
                    $user->assignRole("Aluno");
                }
            }
        });
    }


}
