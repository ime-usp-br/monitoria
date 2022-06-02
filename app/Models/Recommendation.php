<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Student;
use App\Models\Requisition;

class Recommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'requisition_id',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function requisition()
    {
        return $this->belongsTo(Requisition::class);
    }
}
