<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Instructor;
use App\Models\Group;
use App\Models\Activity;

class TeachingAssistantApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'instructor_id',
        'group_id',
        'requested_number',
        'priority',
    ];

    public function instructor(){
        return $this->belongsTo(Instructor::class);
    }

    public function group(){
        return $this->belongsTo(Group::class);
    }

    public function activities(){
        return $this->belongsToMany(Activity::class);
    }
}
