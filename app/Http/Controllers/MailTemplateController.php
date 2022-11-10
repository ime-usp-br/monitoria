<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMailTemplateRequest;
use App\Http\Requests\UpdateMailTemplateRequest;
use App\Http\Requests\TestMailTemplateRequest;
use App\Models\MailTemplate;
use App\Models\Selection;
use App\Models\Frequency;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotifyInstructorAboutAttendanceRecord;
use App\Mail\NotifyInstructorAboutSelectAssistant;
use App\Mail\NotifySelectStudent;
use App\Mail\NotifyStudentAboutSelfEvaluation;
use App\Mail\NotifyInstructorAboutEvaluation;
use \Illuminate\Support\Facades\URL;
use Session;

class MailTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(!Gate::allows('Editar E-mails')){
            abort(403);
        }

        $mailtemplates = MailTemplate::all();

        return view("mailtemplates.index", compact("mailtemplates"));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if(!Gate::allows('Editar E-mails')){
            abort(403);
        }

        $mailtemplate = new MailTemplate;

        return view("mailtemplates.create", compact("mailtemplate"));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreMailTemplateRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreMailTemplateRequest $request)
    {
        if(!Gate::allows('Editar E-mails')){
            abort(403);
        }
        
        $validated = $request->validated();

        $description_and_class_name = json_decode($validated["description_and_mail_class"]);
        $validated["description"] = $description_and_class_name->description;
        $validated["mail_class"] = $description_and_class_name->mail_class;
        unset($validated["description_and_mail_class"]);
        
        if(MailTemplate::where("name",$validated["name"])->exists()){
            Session::flash('alert-warning', 'Já existe um modelo com esse nome.');
            return back();
        }

        MailTemplate::create($validated);

        return redirect("mailtemplates");
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MailTemplate  $mailTemplate
     * @return \Illuminate\Http\Response
     */
    public function show(MailTemplate $mailTemplate)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\MailTemplate  $mailTemplate
     * @return \Illuminate\Http\Response
     */
    public function edit(MailTemplate $mailtemplate)
    {
        if(!Gate::allows('Editar E-mails')){
            abort(403);
        }
        
        return view("mailtemplates.edit", compact("mailtemplate"));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateMailTemplateRequest  $request
     * @param  \App\Models\MailTemplate  $mailTemplate
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateMailTemplateRequest $request, MailTemplate $mailtemplate)
    {
        if(!Gate::allows('Editar E-mails')){
            abort(403);
        }
        
        $validated = $request->validated();

        $description_and_class_name = json_decode($validated["description_and_mail_class"]);
        $validated["description"] = $description_and_class_name->description;
        $validated["mail_class"] = $description_and_class_name->mail_class;
        unset($validated["description_and_mail_class"]);
        
        if(MailTemplate::where("name",$validated["name"])->where("id", "!=", $mailtemplate->id)->exists()){
            Session::flash('alert-warning', 'Já existe um modelo com esse nome.');
            return back();
        }
        
        if(MailTemplate::where("mail_class", $validated["mail_class"])
                ->where("id","!=", $mailtemplate->id)
                ->where("active",true)->where("sending_frequency", "Manual")->exists() and
                $validated["sending_frequency"] == "Manual"){
            Session::flash('alert-warning', 'Já existe um modelo ativo com essa aplicação para disparo manual.');
            return back();
        }

        if($validated["sending_frequency"]=="Manual"){
            $validated["sending_date"] = null;
            $validated["sending_hour"] = null;
        }

        $mailtemplate->update($validated);

        return redirect("mailtemplates");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MailTemplate  $mailTemplate
     * @return \Illuminate\Http\Response
     */
    public function destroy(MailTemplate $mailtemplate)
    {
        if(!Gate::allows('Editar E-mails')){
            abort(403);
        }

        $mailtemplate->delete();

        return back();
    }

    public function activate(MailTemplate $mailtemplate)
    {
        if(!Gate::allows('Editar E-mails')){
            abort(403);
        }
        
        if(MailTemplate::where("mail_class", $mailtemplate->mail_class)
                ->where("id","!=", $mailtemplate->id)
                ->where("active",true)->where("sending_frequency", "Manual")->exists() and
                $mailtemplate->sending_frequency == "Manual"){
            Session::flash('alert-warning', 'Já existe um modelo ativo com essa aplicação para disparo manual.');
            return back();
        }
        
        $mailtemplate->active = true;
        $mailtemplate->save();

        return back();
    }

    public function deactivate(MailTemplate $mailtemplate)
    {
        if(!Gate::allows('Editar E-mails')){
            abort(403);
        }

        $mailtemplate->active = false;
        $mailtemplate->save();


        return back();
    }

    public function test(TestMailTemplateRequest $request)
    {
        if(!Gate::allows('Disparar emails')){
            abort(403);
        }

        $validated = $request->validated();
        
        $mailtemplate = MailTemplate::find($validated["mailtemplate_id"]);

        if($mailtemplate->mail_class == "NotifyInstructorAboutAttendanceRecord"){
            $frequency = Frequency::whereHas("schoolclass.selections", function($query){
                $query->where("sitatl","Ativo");
            })->first() ?? Frequency::first();

            if(!$frequency){
                Session::flash('alert-warning', 'Não foi encontrada nenhuma frequência para ser usada de exemplo.');
                return back();
            }

            Mail::to($validated["email"])->send(new NotifyInstructorAboutAttendanceRecord($frequency,
                URL::signedRoute('frequencies.show', ['schoolclass'=>$frequency->schoolclass->id,'tutor'=>$frequency->student->id]), $mailtemplate));
            
        }elseif($mailtemplate->mail_class == "NotifyInstructorAboutSelectAssistant"){
            $selection = Selection::latest()->first();

            if(!$selection){
                Session::flash('alert-warning', 'Não foi encontrado nenhum monitor para ser usado de exemplo.');
                return back();
            }

            Mail::to($validated["email"])->send(new NotifyInstructorAboutSelectAssistant($selection->schoolclass, $mailtemplate));
            
        }elseif($mailtemplate->mail_class == "NotifySelectStudent"){
            $selection = Selection::latest()->first();

            if(!$selection){
                Session::flash('alert-warning', 'Não foi encontrado nenhum monitor para ser usado de exemplo.');
                return back();
            }

            Mail::to($validated["email"])->send(new NotifySelectStudent($selection->student, $selection->schoolclass, $mailtemplate));
        }elseif($mailtemplate->mail_class == "NotifyStudentAboutSelfEvaluation"){
            $selection = Selection::latest()->first();

            if(!$selection){
                Session::flash('alert-warning', 'Não foi encontrado nenhum monitor para ser usado de exemplo.');
                return back();
            }

            Mail::to($validated["email"])->send(new NotifyStudentAboutSelfEvaluation($selection,
            URL::signedRoute('selfevaluations.create', ['selectionID'=>$selection->id]), $mailtemplate));
        }elseif($mailtemplate->mail_class == "NotifyInstructorAboutEvaluation"){
            $selection = Selection::latest()->first();

            if(!$selection){
                Session::flash('alert-warning', 'Não foi encontrado nenhum monitor para ser usado de exemplo.');
                return back();
            }

            Mail::to($validated["email"])->send(new NotifyStudentAboutSelfEvaluation($selection,
            URL::signedRoute('instructorevaluations.create', ['selectionID'=>$selection->id]), $mailtemplate));
        }

        Session::flash('alert-info', 'O e-mail de teste foi enviado com sucesso.');
        return back();
    }
}
