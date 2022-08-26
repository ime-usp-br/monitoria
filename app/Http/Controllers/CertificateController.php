<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Selection;
use App\Models\Student;
use Ismaelw\LaraTeX\LaraTeX;
use Auth;
use Session;

class CertificateController extends Controller
{
    public function index()
    {
        if(!Auth::user()->hasRole("Aluno")){
            abort(403);
        }
        
        $selections = Selection::whereBelongsTo(Student::where("codpes", Auth::user()->codpes)->first())
            ->where("sitatl", "Concluido")->get();

        if($selections->isEmpty()){
            Session::flash("alert-warning", "Você não concluiu nenhuma monitoria.");
            return back();
        }

        return view('certificates.index', compact('selections'));
    }

    public function make(Selection $selection)
    {
        if(!Auth::user()->hasRole("Aluno")){
            abort(403);
        }elseif($selection->student_id != Student::where("codpes", Auth::user()->codpes)->first()->id){
            abort(403);
        }
        
        return (new LaraTeX('certificates.latex'))->with([
            'selection' => $selection,
        ])->download('atestado.pdf');
    }
}
