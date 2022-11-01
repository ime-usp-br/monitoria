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
            ->where("sitatl", "!=", "Desligado")->get()->sortBy(["schoolclass.schoolterm.year", "schoolclass.schoolterm.period"])->reverse();

        if($selections->isEmpty()){
            Session::flash("alert-warning", "Você não realizou nenhuma monitoria.");
            return back();
        }

        return view('certificates.index', compact('selections'));
    }

    public function make(Selection $selection)
    {
        if(Auth::check()){
            if($selection->student_id != Student::where("codpes", Auth::user()->codpes)->first()->id){
                abort(403);
            }
        }else{
            return redirect("login");
        }

        if($selection->sitatl == "Concluido"){
            return (new LaraTeX('certificates.completed'))->with([
                'selection' => $selection,
            ])->download('atestado.pdf');
        }elseif($selection->sitatl == "Ativo"){
            return (new LaraTeX('certificates.ongoing'))->with([
                'selection' => $selection,
            ])->download('atestado.pdf');
        }
    }
}
