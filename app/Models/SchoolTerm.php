<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SchoolClass;
use App\Models\SchoolRecord;

class SchoolTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'period',
        'status',
        'evaluation_period',
        'max_enrollments',
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

    public function schoolclasses()
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function schoolrecords()
    {
        return $this->hasMany(SchoolRecord::class);
    }

    public static function isEnrollmentPeriod()
    {
        return SchoolTerm::where('start_date_student_registration', '<=', now())
            ->where('end_date_student_registration', '>=', now())->first() ? 1 : 0;
    }

    public static function getSchoolTermInEnrollmentPeriod()
    {
        return SchoolTerm::where('start_date_student_registration', '<=', now())
        ->where('end_date_student_registration', '>=', now())->first();
    }
}
