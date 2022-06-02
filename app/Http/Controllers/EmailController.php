<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SchoolClass;
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
        
        foreach($validated['school_classes_id'] as $id){
            $turma = SchoolClass::find($id);
            $docente = $turma->requisition->instructor;

            Mail::to($docente->codema)->send(new NotifyInstructorAboutSelectAssistant($turma));

            foreach($turma->selections as $selecao){
                $estudante = $selecao->student;

                Mail::to($estudante->codema)->send(new NotifySelectStudent($estudante));
            }
        }

        Session::flash('alert-info', 'Os emails foram adicionados a fila e serão enviados assim que possível');
        return back();
    }
}
