<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexTutorRequest;
use App\Http\Requests\RevokeTutoringRequest;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\SchoolTerm;
use App\Models\Selection;
use App\Models\Instructor;
use Auth;
use Session;


class TutorController extends Controller
{
    public function index(IndexTutorRequest $request)
    {
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

        if(Auth::check()){
            if(Auth::user()->hasRole(["Administrador", "Secretaria", "Presidente de Comissão"])){
                $selections = Selection::whereHas("schoolclass", function($query)use($schoolterm){
                    $query->whereBelongsTo($schoolterm);
                })->get()->sortBy("student.nompes");
            }elseif(Auth::user()->hasRole("Membro Comissão")){
                $selections = Selection::whereHas("schoolclass", function($query)use($schoolterm){
                    $query->whereBelongsTo(Instructor::where("codpes",Auth::user()->codpes)->first()->department)
                        ->whereBelongsTo($schoolterm);
                    })->get()->sortBy("student.nompes");
            }elseif(Auth::user()->hasRole("Docente")){
                $selections = Selection::whereHas("schoolclass", function($query)use($schoolterm){
                    $query->whereBelongsTo($schoolterm);
                })->whereHas("requisition", function($query){
                    $query->whereBelongsTo(Instructor::where("codpes", Auth::user()->codpes)->first());
                })->get()->sortBy("student.nompes");
            }else{
                abort(403);
            }
        }else{
            abort(403);
        }

        return view('tutors.index', compact(['selections', 'schoolterm']));
    }

    public function revoke(RevokeTutoringRequest $request, Selection $selection)
    {
        if(Auth::check()){
            if(!Auth::user()->hasRole(["Secretaria", "Administrador"])){
                abort(403);
            }
        }else{
            return redirect("login");
        }

        if($selection->sitatl != "Ativo"){
            Session::flash('alert-warning', 'Esta monitoria encontra-se com status '.$selection->sitatl.'.');
            return back();  
        }

        $validated = $request->validated();

        $selection->sitatl = "Desligado";
        $selection->motdes = $validated["motdes"];
        $selection->dtafimvin = date("d/m/Y");

        $selection->update();

        Session::flash('alert-info', 'Monitor desligado com sucesso.');

        return back();
    }

    public function turnIntoVolunteer(Selection $selection)
    {
        if(Auth::check()){
            if(!Auth::user()->hasRole(["Secretaria", "Administrador"])){
                abort(403);
            }
        }else{
            return redirect("login");
        }

        if($selection->sitatl == "Ativo"){
            $selection->enrollment->voluntario = 1;
            $selection->enrollment->save();
    
            Session::flash('alert-success',  ($selection->student->getSexo() == "F" ? "A monitora " : "O monitor ").$selection->student->nompes." passou a ser voluntári".($selection->student->getSexo() == "F" ? "a." : "o." ));
            return back();
        }else{
            Session::flash('alert-warning', 'Esta monitoria encontra-se com status '.$selection->sitatl.'.');
            return back();
        }
    }

    public function turnIntoNonVolunteer(Selection $selection)
    {
        if(Auth::check()){
            if(!Auth::user()->hasRole(["Secretaria", "Administrador"])){
                abort(403);
            }
        }else{
            return redirect("login");
        }
        if($selection->sitatl == "Ativo"){
            $selection->enrollment->voluntario = 0;
            $selection->enrollment->save();
    
            Session::flash('alert-success', ($selection->student->getSexo() == "F" ? "A monitora " : "O monitor " ).$selection->student->nompes." deixou de ser voluntári".($selection->student->getSexo() == "F" ? "a." : "o." ));
            return back();
        }else{
            Session::flash('alert-warning', 'Esta monitoria encontra-se com status '.$selection->sitatl.'.');
            return back();
        }

    }
}
