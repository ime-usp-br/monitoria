<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SchoolClass;
use App\Models\MailTemplate;
use App\Http\Requests\DispatchEmailsRequest;
use App\Mail\NotifySelectStudent;
use App\Mail\NotifyInstructorAboutSelectAssistant;
use Illuminate\Support\Facades\Mail;
use Session;

class EmailController extends Controller
{
    public function index()
    {
        $turmas = SchoolClass::whereInOpenSchoolTerm()->whereHas('selections')->get();

        return view('emails.index', compact('turmas'));
    }

    public function dispatchForAll(DispatchEmailsRequest $request)
    {
        $validated = $request->validated();
        
        $mailtemplate_instructor = MailTemplate::where("mail_class", "NotifyInstructorAboutSelectAssistant")->where("active", true)->where("sending_frequency", "Manual")->first();

        if(!$mailtemplate_instructor){
            Session::flash('alert-warning', 'Não foi encontrado nenhum modelo de e-mail ativo com frequência manual para notificar o docente sobre o resultado da seleção.');
            return back();
        }
        
        $mailtemplate_tutor = MailTemplate::where("mail_class", "NotifySelectStudent")->where("active", true)->where("sending_frequency", "Manual")->first();

        if(!$mailtemplate_tutor){
            Session::flash('alert-warning', 'Não foi encontrado nenhum modelo de e-mail ativo com frequência manual para notificar o monitor sobre o resultado da seleção.');
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
}
