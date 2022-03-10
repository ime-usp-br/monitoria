<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\SchoolTerm;
use App\Models\SchoolClass;
use App\Models\ClassSchedule;
use App\Models\Instructor;

class ProcessGetSchoolClassesFromReplicado implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $schoolterm;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(SchoolTerm $schoolterm)
    {
        $this->schoolterm = $schoolterm;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $turmas = SchoolClass::getFromReplicadoBySchoolTerm($this->schoolterm);
        foreach($turmas as $turma){
            $schoolclass = SchoolClass::where(array_intersect_key($turma, array_flip(array('codtur', 'coddis'))))->first();

            if(!$schoolclass){
                $schoolclass = new SchoolClass;
                $schoolclass->fill($turma);
                $schoolclass->save();
        
                foreach($turma['instructors'] as $instructor){
                    $schoolclass->instructors()->attach(Instructor::firstOrCreate($instructor));
                }
        
                foreach($turma['class_schedules'] as $classSchedule){
                    $schoolclass->classschedules()->attach(ClassSchedule::firstOrCreate($classSchedule));
                }
                $schoolclass->save();
            }
        }
    }
}
