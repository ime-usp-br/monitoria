<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TeachingAssistantApplication;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'description'
    ];

    public function teachingAssistantApplications(){
        return $this->belongsToMany(TeachingAssistantApplication::class);
    }
}
