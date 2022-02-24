<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Group;

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
}
