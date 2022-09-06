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
        }

        if(!$schoolterm){
            Session::flash('alert-warning', 'Não foi encontrado um periodo letivo.');
            return back();
        }

        if(Auth::check()){
            if(Auth::user()->hasRole(["Administrador", "Secretaria", "Presidente de Comissão"])){
                $selections = Selection::whereHas("schoolclass", function($query)use($schoolterm){
                    $query->whereBelongsTo($schoolterm);
                })->orderBy("created_at")->get();
            }elseif(Auth::user()->hasRole("Membro Comissão")){
                $selections = Selection::whereHas("schoolclass", function($query)use($schoolterm){
                    $query->whereBelongsTo(Instructor::where("codpes",Auth::user()->codpes)->first()->department)
                        ->whereBelongsTo($schoolterm);
                })->orderBy("created_at")->get();
            }elseif(Auth::user()->hasRole("Docente")){
                $selections = Selection::whereHas("schoolclass", function($query)use($schoolterm){
                    $query->whereBelongsTo($schoolterm);
                })->whereHas("requisition", function($query){
                    $query->whereBelongsTo(Instructor::where("codpes", Auth::user()->codpes)->first());
                })->orderBy("created_at")->get();
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
        $validated = $request->validated();

        $selection->sitatl = "Desligado";
        $selection->motdes = $validated["motdes"];
        $selection->dtafimvin = date("d/m/Y");

        $selection->update();

        Session::flash('alert-info', 'Monitor desligado com sucesso.');

        return back();


    }
}
