<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\TriggerSelectionsRequest;
use App\Http\Requests\TriggerAttendanceRecordsRequest;
use App\Http\Requests\TriggerSelfEvaluationsRequest;
use App\Http\Requests\TriggerInstructorEvaluationsRequest;
use App\Http\Requests\IndexAttendanceRecordsRequest;
use App\Mail\NotifySelectStudent;
use App\Mail\NotifyInstructorAboutSelectAssistant;
use App\Mail\NotifyInstructorAboutAttendanceRecord;
use App\Mail\NotifyStudentAboutSelfEvaluation;
use App\Mail\NotifyInstructorAboutEvaluation;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Gate;
use \Illuminate\Support\Facades\URL;
use App\Models\SchoolClass;
use App\Models\MailTemplate;
use App\Models\SchoolTerm;
use App\Models\Frequency;
use App\Models\Selection;
use Session;

class EmailController extends Controller
{
    public function index()
    {
        if(!Gate::allows('Disparar emails')){
            abort(403);
        }

        return view('emails.index');
    }

    public function indexSelections()
    {
        if(!Gate::allows('Disparar emails')){
            abort(403);
        }

        $schoolterm = SchoolTerm::getOpenSchoolTerm();

        if(!$schoolterm){
            $schoolterm = SchoolTerm::getLatest();
        }

        if(!$schoolterm){
            Session::flash('alert-warning', 'Não foi encontrado um periodo letivo.');
            return back();
        }

        $turmas = SchoolClass::whereBelongsTo($schoolterm)->whereHas('selections')->get();

        return view('emails.indexSelections', compact(["turmas", "schoolterm"]));
    }
    
    public function indexAttendanceRecords(IndexAttendanceRecordsRequest $request)
    {
        if(!Gate::allows('Disparar emails')){
            abort(403);
        }

        $schoolterm = SchoolTerm::getOpenSchoolTerm();

        if(!$schoolterm){
            $schoolterm = SchoolTerm::getLatest();
        }

        if(!$schoolterm){
            Session::flash('alert-warning', 'Não foi encontrado um periodo letivo.');
            return back();
        }

        $months = $schoolterm->period == "1° Semestre" ? [3,4,5,6] : [8,9,10,11];

        $validated = $request->validated();

        if(isset($validated['month'])){
            if(in_array($validated['month'], $months)){
                $month = $validated["month"];
            }else{
                Session::flash('alert-warning', 'Mês invalido para o '.$schoolterm->period.'.');
                return back();
            }
        }else{
            $month = date("d") >= 20 ? date("m") : ((int)date("m"))-1; 
            if(!in_array($month, $months)){
                $month_for_comparison_str = str_pad((int)$month, 2, '0', STR_PAD_LEFT);
                $current_year_for_comparison = date("Y");
                if ((int)$month == 0 && (int)date("m") == 1 && (int)date("d") < 20) {
                    $current_year_for_comparison = (int)date("Y") - 1;
                    $month_for_comparison_str = "12";
                }


                if($current_year_for_comparison."-".$month_for_comparison_str < $schoolterm->year."-".str_pad($months[0], 2, '0', STR_PAD_LEFT)){
                    $month = $months[0];
                }elseif($current_year_for_comparison."-".$month_for_comparison_str > $schoolterm->year."-".str_pad($months[3], 2, '0', STR_PAD_LEFT)){
                    $month = $months[3];
                }
            }
        }

        $month = (int)$month;

        $frequencies = Frequency::whereHas("schoolclass", function($query)use($schoolterm){
            $query->whereBelongsTo($schoolterm);
        })->whereHas("schoolclass.selections", function($query){
            $query->where("sitatl","Ativo");
        })->where("month", $month)->where("registered",false)->get()->sortBy("student.nompes"); // $month is now an int for the DB query

        return view('emails.indexAttendanceRecords', compact(["schoolterm","frequencies", "month"]));
    }

    public function indexSelfEvaluations()
    {
        if(!Gate::allows('Disparar emails')){
            abort(403);
        }

        $schoolterm = SchoolTerm::getOpenSchoolTerm();

        if(!$schoolterm){
            $schoolterm = SchoolTerm::getLatest();
        }

        if(!$schoolterm){
            Session::flash('alert-warning', 'Não foi encontrado um periodo letivo.');
            return back();
        }

        $selections = Selection::whereHas("schoolclass", function($query)use($schoolterm){
            $query->whereBelongsTo($schoolterm);
        })->where("sitatl", "!=", "Desligado")->doesntHave("selfevaluation")->get()->sortBy("student.nompes");

        return view('emails.indexSelfEvaluations', compact(["schoolterm", "selections"]));
    }

    public function indexInstructorEvaluations()
    {
        if(!Gate::allows('Disparar emails')){
            abort(403);
        }

        $schoolterm = SchoolTerm::getOpenSchoolTerm();

        if(!$schoolterm){
            $schoolterm = SchoolTerm::getLatest();
        }

        if(!$schoolterm){
            Session::flash('alert-warning', 'Não foi encontrado um periodo letivo.');
            return back();
        }

        $selections = Selection::whereHas("schoolclass", function($query)use($schoolterm){
            $query->whereBelongsTo($schoolterm);
        })->where("sitatl", "!=", "Desligado")->doesntHave("instructorevaluation")->get()->sortBy("requisition.instructor.nompes");

        return view('emails.indexInstructorEvaluations', compact(["schoolterm", "selections"]));
    }

    public function triggerSelections(TriggerSelectionsRequest $request)
    {
        if(!Gate::allows('Disparar emails')){
            abort(403);
        }

        $validated = $request->validated();
        
        $mailtemplate_instructor = MailTemplate::where("mail_class", "NotifyInstructorAboutSelectAssistant")->where("active", true)->where("sending_frequency", "Manual")->first();

        if(!$mailtemplate_instructor){
            Session::flash('alert-warning', 'Não foi encontrado nenhum modelo de e-mail ativo com frequência manual para notificar os docentes sobre o resultado da seleção.');
            return back();
        }
        
        $mailtemplate_tutor = MailTemplate::where("mail_class", "NotifySelectStudent")->where("active", true)->where("sending_frequency", "Manual")->first();

        if(!$mailtemplate_tutor){
            Session::flash('alert-warning', 'Não foi encontrado nenhum modelo de e-mail ativo com frequência manual para notificar os monitores sobre o resultado da seleção.');
            return back();
        }

        foreach($validated['school_classes_id'] as $id){
            $turma = SchoolClass::find($id);
            $docente = $turma->requisition->instructor;

            Mail::to($docente->codema)->send(new NotifyInstructorAboutSelectAssistant($turma, $mailtemplate_instructor));

            foreach($turma->selections as $selecao){
                $estudante = $selecao->student;

                Mail::to($estudante->codema)->send(new NotifySelectStudent($estudante, $turma, $mailtemplate_tutor));
            }
        }

        Session::flash('alert-info', 'Os emails foram adicionados a fila e serão enviados assim que possível');
        return back();
    }

    public function triggerAttendanceRecords(TriggerAttendanceRecordsRequest $request)
    {
        if(!Gate::allows('Disparar emails')){
            abort(403);
        }

        $validated = $request->validated();

        $mailtemplate = MailTemplate::where("mail_class", "NotifyInstructorAboutAttendanceRecord")->where("active", true)->where("sending_frequency", "Manual")->first();

        if(!$mailtemplate){
            Session::flash('alert-warning', 'Não foi encontrado nenhum modelo de e-mail ativo com frequência manual para notificar os docentes sobre a frequência dos monitores.');
            return back();
        }

        foreach($validated["frequencies_id"] as $id){
            $frequency = Frequency::find($id);

            Mail::to($frequency->schoolclass->requisition->instructor->codema)->send(new NotifyInstructorAboutAttendanceRecord($frequency,
                URL::signedRoute('frequencies.show', ['schoolclass'=>$frequency->schoolclass->id,'tutor'=>$frequency->student->id]), $mailtemplate));
        }

        Session::flash('alert-info', 'Os emails foram adicionados a fila e serão enviados assim que possível');
        return back();
    }

    public function triggerSelfEvaluations(TriggerSelfEvaluationsRequest $request)
    {
        if(!Gate::allows('Disparar emails')){
            abort(403);
        }

        $validated = $request->validated();

        $mailtemplate = MailTemplate::where("mail_class", "NotifyStudentAboutSelfEvaluation")->where("active", true)->where("sending_frequency", "Manual")->first();

        if(!$mailtemplate){
            Session::flash('alert-warning', 'Não foi encontrado nenhum modelo de e-mail ativo com frequência manual para notificar os monitores sobre a auto avaliação.');
            return back();
        }

        foreach($validated["selections_id"] as $id){
            $selection = Selection::find($id);

            Mail::to($selection->student->codema)->send(new NotifyStudentAboutSelfEvaluation($selection,
            URL::signedRoute('selfevaluations.create', ['selectionID'=>$selection->id]), $mailtemplate));
        }

        Session::flash('alert-info', 'Os emails foram adicionados a fila e serão enviados assim que possível');
        return back();
    }

    public function triggerInstructorEvaluations(TriggerInstructorEvaluationsRequest $request)
    {
        if(!Gate::allows('Disparar emails')){
            abort(403);
        }

        $validated = $request->validated();

        $mailtemplate = MailTemplate::where("mail_class", "NotifyInstructorAboutEvaluation")->where("active", true)->where("sending_frequency", "Manual")->first();

        if(!$mailtemplate){
            Session::flash('alert-warning', 'Não foi encontrado nenhum modelo de e-mail ativo com frequência manual para notificar os docentes sobre a avaliação.');
            return back();
        }

        foreach($validated["selections_id"] as $id){
            $selection = Selection::find($id);

            Mail::to($selection->requisition->instructor->codema)->send(new NotifyInstructorAboutEvaluation($selection,
            URL::signedRoute('instructorevaluations.create', ['selectionID'=>$selection->id]), $mailtemplate));
        }

        Session::flash('alert-info', 'Os emails foram adicionados a fila e serão enviados assim que possível');
        return back();
    }
}
