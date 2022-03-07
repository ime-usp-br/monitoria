<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Instructor;
use App\Models\SchoolClass;
use App\Models\Activity;

class Requisition extends Model
{
    use HasFactory;

    protected $fillable = [
        'instructor_id',
        'school_class_id',
        'requested_number',
        'priority',
    ];

    public function instructor(){
        return $this->belongsTo(Instructor::class, 'instructor_id');
    }

    public function schoolclass(){
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function activities(){
        return $this->belongsToMany(Activity::class);
    }

    public function hasActivity($activity){
        foreach($this->activities as $act){
            if($act->description == $activity){
                return true;
            }
        }
        return false;
    }

    public function getPriority(){
        $res = [3=>'Imprescindivel',
                2=>'Extremamente necessário, mas não imprescindivel',
                1=>'Importante, porém posso abrir mão do auxilio de um monitor'];

        return $res[$this->priority];
    }
}
