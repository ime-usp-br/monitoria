<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Selection;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\SchoolTerm;

class SelfEvaluation extends Model
{
    use HasFactory;
    use \Znck\Eloquent\Traits\BelongsToThrough;


    protected $fillable = [
        'selection_id',
        'student_amount',
        'homework_amount',
        'secondary_activity',
        'workload',
        'workload_reason',
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

    public function getWorkloadAsString()
    {
        $workloadAsString = [0=>"ótimo", 1=>"bom", 2=>"regular"];

        return $workloadAsString[$this->workload];
    }
}
