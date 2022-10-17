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
        if(Auth::check()){
            if(!Auth::user()->hasRole("Aluno")){
                if(Selection::whereHas("student", function($query){$query->where("codpes",Auth::user()->codpes);})->get()->isEmpty()){
                    abort(403);
                }
            }
        }else{
            return redirect("login");
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
        if($selection->student_id != Student::where("codpes", Auth::user()->codpes)->first()->id){
            abort(403);
        }
        
        return (new LaraTeX('certificates.latex'))->with([
            'selection' => $selection,
        ])->download('atestado.pdf');
    }
}
