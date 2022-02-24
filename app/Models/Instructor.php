<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Group;

class Instructor extends Model
{
    use HasFactory;

    protected $fillable = [
        'codpes',
        'nompes',
    ];

    public function groups()
    {
        return $this->belongsToMany(Group::class);
    }

}
