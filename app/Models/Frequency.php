<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Selection;

class Frequency extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_class_id',
        'student_id',
        'month',
        'registered',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, "student_id");
    }

    public function schoolclass()
    {
        return $this->belongsTo(SchoolClass::class, "school_class_id");
    }

    public static function createFromSelection(Selection $selection)
    {
        $months = $selection->schoolclass->schoolterm->period == "1° Semestre" ? [3,4,5,6,7] : [8,9,10,11,12];

        $frequencies = [];

        foreach($months as $month){
            $frequency = new Frequency;

            $frequency->schoolclass()->associate($selection->schoolclass);
            $frequency->student()->associate($selection->student);
            $frequency->month = $month;

            $frequency->save();

            array_push($frequencies, $frequency);
        }

        return $frequencies;
    }
}
