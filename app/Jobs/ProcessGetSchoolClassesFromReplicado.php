<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use romanzipp\QueueMonitor\Traits\IsMonitored;
use App\Models\SchoolTerm;
use App\Models\SchoolClass;
use App\Models\ClassSchedule;
use App\Models\Instructor;

class ProcessGetSchoolClassesFromReplicado implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, IsMonitored;

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

    public function progressCooldown(): int
    {
        return 1; 
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->queueProgress(0);

        $turmas = SchoolClass::getFromReplicadoBySchoolTerm($this->schoolterm);

        $this->queueProgress(20);
        $t = count($turmas);
        $n = 0;

        foreach($turmas as $turma){
            $schoolclass = SchoolClass::where(array_intersect_key($turma, array_flip(array('codtur', 'coddis'))))->first();

            if(!$schoolclass){
                $schoolclass = new SchoolClass;
                $schoolclass->fill($turma);
                $schoolclass->save();
        
                foreach($turma['instructors'] as $instructor){
                    $schoolclass->instructors()->attach(Instructor::firstOrCreate(Instructor::getFromReplicadoByCodpes($instructor["codpes"])));
                }
        
                foreach($turma['class_schedules'] as $classSchedule){
                    $schoolclass->classschedules()->attach(ClassSchedule::firstOrCreate($classSchedule));
                }
                $schoolclass->save();
            }
            $n += 1;
            $this->queueProgress(20 + floor($n*80/$t));
        }
    }
}
