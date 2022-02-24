<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Group;

class SchoolTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'period',
        'status',
        'evaluation_period',
        'started_at',
        'finished_at',
        'start_date_teacher_requests',
        'end_date_teacher_requests',
        'start_date_student_registration',
        'end_date_student_registration',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'start_date_teacher_requests' => 'datetime',
        'end_date_teacher_requests' => 'datetime',
        'start_date_student_registration' => 'datetime',
        'end_date_student_registration' => 'datetime',
    ];

    public function groups()
    {
        return $this->hasMany(Group::class);
    }
}
