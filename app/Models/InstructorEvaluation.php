<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Selection;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\SchoolTerm;
use App\Models\Instructor;
use App\Models\Requisition;

class InstructorEvaluation extends Model
{
    use HasFactory;
    use \Znck\Eloquent\Traits\BelongsToThrough;

    public static $eval_as_string = [
        0=>"Ótimo",
        1=>'Bom',
        2=>'Regular'
    ];

    protected $fillable = [
        'selection_id',
        'ease_of_contact',
        'efficiency',
        'reliability',
        'overall',
        'comments',
    ];

    public function selection()
    {
        return $this->belongsTo(Selection::class, "selection_id");
    }

    public function student()
    {
        return $this->belongsToThrough(Student::class, Selection::class);
    }

    public function schoolclass()
    {
        return $this->belongsToThrough(SchoolClass::class, Selection::class);
    }

    public function schoolterm()
    {
        return $this->belongsToThrough(SchoolTerm::class, [SchoolClass::class, Selection::class]);
    }

    public function instructor()
    {
        return $this->belongsToThrough(Instructor::class, [Requisition::class, Selection::class]);
    }

    public function getEaseOfContactAsString()
    {
        return InstructorEvaluation::$eval_as_string[$this->ease_of_contact];
    }

    public function getEfficiencyAsString()
    {
        return InstructorEvaluation::$eval_as_string[$this->efficiency];
    }

    public function getReliabilityAsString()
    {
        return InstructorEvaluation::$eval_as_string[$this->reliability];
    }

    public function getOverallAsString()
    {
        return InstructorEvaluation::$eval_as_string[$this->overall];
    }
}
