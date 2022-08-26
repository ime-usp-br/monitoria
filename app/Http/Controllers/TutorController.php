<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexTutorRequest;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\SchoolTerm;
use App\Models\Selection;
use App\Models\Instructor;
use Auth;


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
                $selections = Selection::all()->sortBy("created_at");
            }elseif(Auth::user()->hasRole("Membro Comissão")){
                $selections = Selection::whereHas("schoolclass", function($query){
                    $query->whereBelongsTo(Instructor::where("codpes",Auth::user()->codpes)->first()->department);
                })->orderBy("created_at")->get();
            }elseif(Auth::user()->hasRole("Docente")){
                $selections = Selection::whereHas("requisition", function($query){
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
}
