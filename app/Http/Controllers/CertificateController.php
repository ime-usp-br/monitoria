<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexCertificateRequest;
use Illuminate\Http\Request;
use App\Models\Selection;
use App\Models\Student;
use App\Models\SchoolTerm;
use Ismaelw\LaraTeX\LaraTeX;
use Auth;
use Session;

class CertificateController extends Controller
{
    public function index(IndexCertificateRequest $request)
    {
        if(Auth::check()){
            $hasSelection = Selection::whereHas("student", function($query){$query->where("codpes",Auth::user()->codpes);})->get()->isNotEmpty();
            if(!Auth::user()->hasRole(["Aluno", "Secretaria", "Administrador"])){
                if(!$hasSelection){
                    abort(403);
                }
            }
        }else{
            return redirect("login");
        }

        $validated = $request->validated();

        if(isset($validated['periodoId'])){
            $schoolterm = SchoolTerm::find($validated['periodoId']);
        }else{
            $schoolterm = SchoolTerm::getOpenSchoolTerm();

            if(!$schoolterm){
                $schoolterm = SchoolTerm::getLatest();
            }
        }

        if(!$schoolterm){
            Session::flash('alert-warning', 'Não foi encontrado um periodo letivo.');
            return back();
        }

        if(Auth::user()->hasRole(["Secretaria", "Administrador"])){
            $selections = Selection::whereHas("schoolclass", function($query)use($schoolterm){
                $query->whereBelongsTo($schoolterm);
            })->where("sitatl", "!=", "Desligado")->get()->sortBy("student.nompes");
        }elseif(Auth::user()->hasRole("Aluno") or $hasSelection){
            $selections = Selection::whereBelongsTo(Student::where("codpes", Auth::user()->codpes)->first())
                ->where("sitatl", "!=", "Desligado")->get()->sortBy(["schoolclass.schoolterm.year", "schoolclass.schoolterm.period"])->reverse();
        }
        

        if($selections->isEmpty()){
            Session::flash("alert-warning", "Você não realizou nenhuma monitoria.");
            return back();
        }

        return view('certificates.index', compact(['selections','schoolterm']));
    }

    public function make(Selection $selection)
    {
        if(Auth::check()){
            if(!Auth::user()->hasRole(["Secretaria", "Administrador"])){
                if($selection->student_id != (Student::where("codpes", Auth::user()->codpes)->first()->id ?? "")){
                    abort(403);
                }
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
