<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use romanzipp\QueueMonitor\Traits\IsMonitored;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Student;
use App\Models\Instructor;
use App\Models\SchoolTerm;
use App\Models\SchoolClass;
use App\Models\Frequency;
use App\Models\Requisition;
use App\Models\Enrollment;
use App\Models\Selection;
use App\Models\ClassSchedule;
use App\Models\Activity;
use App\Models\SelfEvaluation;
use App\Models\InstructorEvaluation;

class ProcessImportOldDB implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, IsMonitored;

    public $timeout = 999;

    public $file, $codpescad;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($file, $codpescad)
    {
        $this->file = $file;
        $this->codpescad = $codpescad;
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

        $data = [];        
        $linhas_com_erros = "";

        $i = 0;
        foreach(explode("\n", $this->file) as $line){
            $i += 1;
            $cols = explode(";", $line);
            if(count($cols) == 18){
                array_push($data,[  
                    "monitor_codpes"=>$cols[0], 
                    "professor_codpes"=>$cols[1], 
                    "coddis"=>$cols[2], 
                    "ano"=>$cols[3], 
                    "semestre"=>$cols[4], 
                    "frequencia_meses"=>$cols[5], 
                    "voluntario"=>$cols[6], 
                    "student_amount"=>$cols[7], 
                    "homework_amount"=>$cols[8], 
                    "secondary_activity"=>$cols[9], 
                    "workload"=>$cols[10], 
                    "workload_reason"=>$cols[11], 
                    "comments"=>$cols[12],
                    "ease_of_contact"=>$cols[13], 
                    "efficiency"=>$cols[14], 
                    "reliability"=>$cols[15], 
                    "overall"=>$cols[16], 
                    "comments_ie"=>$cols[17], 
                    "original_line_number"=>$i
                ]);
            }else{
                $linhas_com_erros .= $i.",";
            }
        }

        $t = count($data);
        $n = 0;

        foreach($data as $line){
            $docente_array = Instructor::getFromReplicadoByCodpes($line["professor_codpes"]);

            if($docente_array){
                $docente = Instructor::firstOrCreate([
                    "codpes"=>$line["professor_codpes"]
                ],$docente_array);
            }else{
                $linhas_com_erros .= $line["original_line_number"].",";
                continue;
            }

            $monitor_array = Student::getFromReplicadoByCodpes($line["monitor_codpes"]);

            if($monitor_array){
                $monitor = Student::firstOrCreate([
                    "codpes"=>$line["monitor_codpes"]
                ],$monitor_array);
            }else{
                $linhas_com_erros .= $line["original_line_number"].",";
                continue;
            }

            $st = SchoolTerm::firstOrCreate(
                [
                    "year"=>$line["ano"],
                    "period"=> $line["semestre"] ? "2° Semestre" : "1° Semestre"
                ],
                [
                    "status"=>"Fechado",
                    "evaluation_period"=>"Fechado",
                    "max_enrollments"=>4,
                    "started_at"=>$line["semestre"] ? "01/08/".$line["ano"] : "01/03/".$line["ano"],
                    "finished_at"=>$line["semestre"] ? "15/12/".$line["ano"] : "15/06/".$line["ano"],
                    "start_date_requisitions"=>$line["semestre"] ? "01/07/".$line["ano"] : "01/02/".$line["ano"],
                    "end_date_requisitions"=>$line["semestre"] ? "30/07/".$line["ano"] : "30/02/".$line["ano"],
                    "start_date_enrollments"=>$line["semestre"] ? "01/07/".$line["ano"] : "01/02/".$line["ano"],
                    "end_date_enrollments"=>$line["semestre"] ? "30/07/".$line["ano"] : "30/02/".$line["ano"],
                ]
            );

            $turma = SchoolClass::getFromReplicadoOldDB($st, $line["professor_codpes"], $line["coddis"]);

            if($turma){
                $schoolclass = SchoolClass::where("coddis",$turma["coddis"])
                                            ->whereBelongsTo($st)
                                            ->whereHas("instructors", function($query)use($docente){
                                                $query->where("codpes", $docente->codpes);
                                            })->first();
            }else{
                $linhas_com_erros .= $line["original_line_number"].",";
                continue;
            }


            if(!$schoolclass){
                $schoolclass = new SchoolClass;
                $schoolclass->fill($turma);
                $schoolclass->save();
            }
            
            $schoolclass->instructors()->detach();
            foreach($turma['instructors'] as $instructor){
                $schoolclass->instructors()->attach(Instructor::firstOrCreate(["codpes"=>$instructor["codpes"]], $instructor));
            }
    
            $schoolclass->classschedules()->detach();
            foreach($turma['class_schedules'] as $classSchedule){
                $schoolclass->classschedules()->attach(ClassSchedule::firstOrCreate($classSchedule));
            }
            $schoolclass->save();

            $activities = ['Atendimento a alunos', 'Correção de listas de exercícios', 'Fiscalização de provas'];

            $requisition = Requisition::firstOrCreate([
                    "school_class_id"=>$schoolclass->id,
                    'instructor_id'=>$docente->id,
                    'requested_number'=>1,
                    'priority'=>1,
                ]);
            
            $requisition->activities()->detach();
            foreach($activities as $act){
                $requisition->activities()->attach(Activity::firstOrCreate(['description'=>$act]));
            }

            $enrollment = Enrollment::updateOrCreate([
                    'school_class_id'=>$schoolclass->id,
                    'student_id'=>$monitor->id,
                    'voluntario'=>$line["voluntario"],
                    'disponibilidade_diurno'=>1,
                    'disponibilidade_noturno'=>1,
                    'preferencia_horario'=>'Indiferente',
                ],[
                    'observacoes'=>"Inscrição importada do antigo sistema de monitoria em ".date("d/m/Y"),
                ]);

            $selection = Selection::updateOrCreate([
                    'student_id'=>$monitor->id,
                    'school_class_id'=>$schoolclass->id,
                    'enrollment_id'=>$enrollment->id,
                    'requisition_id'=>$requisition->id,
                    'selecionado_sem_inscricao'=>0,
                    'sitatl'=>"Concluido"
                ],[
                    'codpescad'=>$this->codpescad,
                ]);
            
            $meses = explode("-", $line["frequencia_meses"]);

            foreach($meses as $mes){
                Frequency::firstOrCreate([
                        'school_class_id'=>$schoolclass->id,
                        'student_id'=>$monitor->id,
                        'month'=>$mes,
                        'registered'=>1,
                    ]);
            }

            if($line["student_amount"]!=null and $line["homework_amount"]!=null and $line["workload"]!=null){
                $se = SelfEvaluation::firstOrCreate([
                    'selection_id'=>$selection->id,
                    'student_amount'=>$line["student_amount"],
                    'homework_amount'=>$line["homework_amount"],
                    'secondary_activity'=>$line["secondary_activity"],
                    'workload'=>$line["workload"],
                    'workload_reason'=>$line["workload_reason"],
                    'comments'=>$line["comments"],
                ]);
            }

            if($line["ease_of_contact"]!=null and $line["efficiency"]!=null and $line["reliability"]!=null and $line["overall"]!=null ){
                $ie = InstructorEvaluation::firstOrCreate([
                    'selection_id'=>$selection->id,
                    'ease_of_contact'=>$line["ease_of_contact"],
                    'efficiency'=>$line["efficiency"],
                    'reliability'=>$line["reliability"],
                    'overall'=>$line["overall"],
                    'comments'=>$line["comments_ie"],
                ]);
            }

            $n += 1;
            $this->queueProgress(floor($n*100/$t));
        }

        if($linhas_com_erros){
            $this->queueData(["status"=>"failed","linhas_com_erros"=>"[".rtrim($linhas_com_erros,",")."]"]);
        }

        $this->queueProgress(100);

    }
}
