<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SchoolClass;
use App\Models\SchoolRecord;
use App\Models\Course;
use Carbon\Carbon;

class SchoolTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'period',
        'status',
        'max_enrollments',
        'public_notice_file_path',
        'started_at',
        'finished_at',
        'start_date_requisitions',
        'end_date_requisitions',
        'start_date_enrollments',
        'end_date_enrollments',
        'start_date_evaluations',
        'end_date_evaluations',
    ];

    protected $casts = [
        'started_at' => 'date:d/m/Y',
        'finished_at' => 'date:d/m/Y',
        'start_date_requisitions' => 'date:d/m/Y',
        'end_date_requisitions' => 'date:d/m/Y',
        'start_date_enrollments' => 'date:d/m/Y',
        'end_date_enrollments' => 'date:d/m/Y',
        'start_date_evaluations' => 'date:d/m/Y',
        'end_date_evaluations' => 'date:d/m/Y',
    ];    

    public function setStartedAtAttribute($value)
    {
        $this->attributes['started_at'] = Carbon::createFromFormat('d/m/Y', $value)->startOfDay();
    }

    public function setFinishedAtAttribute($value)
    {
        $this->attributes['finished_at'] = Carbon::createFromFormat('d/m/Y', $value)->endOfDay();
    }

    public function setStartDateEvaluationsAttribute($value)
    {
        $this->attributes['start_date_evaluations'] = Carbon::createFromFormat('d/m/Y', $value)->startOfDay();
    }

    public function setEndDateEvaluationsAttribute($value)
    {
        $this->attributes['end_date_evaluations'] = Carbon::createFromFormat('d/m/Y', $value)->endOfDay();
    }

    public function setStartDateRequisitionsAttribute($value)
    {
        $this->attributes['start_date_requisitions'] = Carbon::createFromFormat('d/m/Y', $value)->startOfDay();
    }

    public function setEndDateRequisitionsAttribute($value)
    {
        $this->attributes['end_date_requisitions'] = Carbon::createFromFormat('d/m/Y', $value)->endOfDay();
    }

    public function setStartDateEnrollmentsAttribute($value)
    {
        $this->attributes['start_date_enrollments'] = Carbon::createFromFormat('d/m/Y', $value)->startOfDay();
    }

    public function setEndDateEnrollmentsAttribute($value)
    {
        $this->attributes['end_date_enrollments'] = Carbon::createFromFormat('d/m/Y', $value)->endOfDay();
    }

    public function getStartedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('d/m/Y') : '';
    }

    public function getFinishedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('d/m/Y') : '';
    }

    public function getStartDateEvaluationsAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('d/m/Y') : '';
    }

    public function getEndDateEvaluationsAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('d/m/Y') : '';
    }

    public function getStartDateRequisitionsAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('d/m/Y') : '';
    }

    public function getEndDateRequisitionsAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('d/m/Y') : '';
    }

    public function getStartDateEnrollmentsAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('d/m/Y') : '';
    }

    public function getEndDateEnrollmentsAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('d/m/Y') : '';
    }

    public function schoolclasses()
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function schoolrecords()
    {
        return $this->hasMany(SchoolRecord::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
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

    public static function isEvaluationPeriod()
    {
        return SchoolTerm::where('start_date_evaluations', '<=', now())
            ->where('end_date_evaluations', '>=', now())->first() ? 1 : 0;
    }

    public function isInEvaluationPeriod()
    {
        return (Carbon::createFromFormat('d/m/Y', $this->start_date_evaluations) <= now() and
                Carbon::createFromFormat('d/m/Y', $this->end_date_evaluations) >= now()) ? 1 : 0;
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

    public static function getSchoolTermInEvaluationPeriod()
    {
        return SchoolTerm::where('start_date_evaluations', '<=', now())
        ->where('end_date_evaluations', '>=', now())->first();
    }

    public static function getOpenSchoolTerm()
    {
        return SchoolTerm::where(['status'=>'Aberto'])->first();
    }

    public static function getLatest()
    {
        $year = SchoolTerm::max("year");
        $period = SchoolTerm::where("year",$year)->max("period");
        return SchoolTerm::where(["year"=>$year,"period"=>$period])->first();
    } 
}
