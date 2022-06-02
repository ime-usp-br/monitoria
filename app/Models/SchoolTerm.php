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
        'start_date_requisitions',
        'end_date_requisitions',
        'start_date_enrollments',
        'end_date_enrollments',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'start_date_requisitions' => 'datetime',
        'end_date_requisitions' => 'datetime',
        'start_date_enrollments' => 'datetime',
        'end_date_enrollments' => 'datetime',
    ];

    public function schoolclasses()
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function schoolrecords()
    {
        return $this->hasMany(SchoolRecord::class);
    }

    public static function isRequisitionPeriod()
    {
        return SchoolTerm::where('start_date_requisitions', '<=', now())
            ->where('end_date_requisitions', '>=', now())->first() ? 1 : 0;
        
    }

    public static function isEnrollmentPeriod()
    {
        return SchoolTerm::where('start_date_enrollments', '<=', now())
            ->where('end_date_enrollments', '>=', now())->first() ? 1 : 0;
    }

    public static function getSchoolTermInRequisitionPeriod()
    {
        return SchoolTerm::where('start_date_requisitions', '<=', now())
        ->where('end_date_requisitions', '>=', now())->first();
    }

    public static function getSchoolTermInEnrollmentPeriod()
    {
        return SchoolTerm::where('start_date_enrollments', '<=', now())
        ->where('end_date_enrollments', '>=', now())->first();
    }

    public static function getOpenSchoolTerm()
    {
        return SchoolTerm::where(['status'=>'Aberto'])->first();
    }
}
