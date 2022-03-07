<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Requisition;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'description'
    ];

    public function requisitions(){
        return $this->belongsToMany(Requisition::class);
    }
}
