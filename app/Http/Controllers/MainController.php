<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ImportOldDBRequest;
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
use Session;
use Auth;


class MainController extends Controller
{
    public function index()
    {
        if(Auth::user()){
            if(Auth::user()->hasRole('Aluno') && !Student::where(['codpes'=>Auth::user()->codpes])->exists()){
                Student::create(Student::getFromReplicadoByCodpes(Auth::user()->codpes));
            }
            if(Auth::user()->hasRole('Docente') && !Instructor::where(['codpes'=>Auth::user()->codpes])->exists()){
                Instructor::create(Instructor::getFromReplicadoByCodpes(Auth::user()->codpes));
            }
        }

        return view('main');
    }

    public function olddbIndex()
    {
        if(Auth::check()){
            if(!Auth::user()->hasRole("Administrador")){
                abort(403);   
            }
        }else{
            return redirect("login");
        }
        return view("olddb.index");
    }

    public function olddbImport(ImportOldDBRequest $request)
    {
        if(Auth::check()){
            if(!Auth::user()->hasRole("Administrador")){
                abort(403);   
            }
        }else{
            return redirect("login");
        }
        $validated = $request->validated();

        $data = [];
        foreach(explode("\n", $validated["file"]->get()) as $line){
            $cols = explode(";", $line);
            if(count($cols) == 13){
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
                    "comments"=>$cols[12]
                ]);
            }
        }
        $errors = "";
        $count_errors = 0;
        foreach($data as $line){
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
                $docente = Instructor::firstOrCreate(Instructor::getFromReplicadoByCodpes($line["professor_codpes"]));

                $schoolclass = SchoolClass::where("coddis",$turma["coddis"])
                                            ->whereBelongsTo($st)
                                            ->whereHas("instructors", function($query)use($docente){
                                                $query->where("codpes", $docente->codpes);
                                            })->first();
    
                if(!$schoolclass){
                    $schoolclass = new SchoolClass;
                    $schoolclass->fill($turma);
                    $schoolclass->save();
                }
                
                $schoolclass->instructors()->detach();
                foreach($turma['instructors'] as $instructor){
                    $schoolclass->instructors()->attach(Instructor::firstOrCreate($instructor));
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

                $monitor = Student::firstOrCreate(Student::getFromReplicadoByCodpes($line["monitor_codpes"]));

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
                        'codpescad'=>Auth::user()->codpes,
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

                if($line["student_amount"] and $line["homework_amount"] and $line["workload"]){
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
            }else{
                $errors .= json_encode($line);
                $count_errors += 1;
            }
        }

        if($errors){
            Session::flash('alert-warning', '<b>Erros:</b> '.$errors.'<br> count('.$count_errors.')');

        }else{
            Session::flash('alert-success', '<b>Atenção:</b> Os dados foram importados com sucesso.');
        }
        return redirect("/");
    }
}
