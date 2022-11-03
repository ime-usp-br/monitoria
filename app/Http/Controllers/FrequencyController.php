<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Gate;
use App\Http\Requests\StoreFrequencyRequest;
use App\Http\Requests\UpdateFrequencyRequest;
use Illuminate\Http\Request;
use App\Models\Frequency;
use App\Models\Selection;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\SchoolTerm;
use Auth;
use Session;

class FrequencyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(Auth::check()){
            if(!Auth::user()->hasRole(["Docente"])){
                abort(403);
            }
        }else{
            return redirect("login");
        }

        $schoolterm = SchoolTerm::getOpenSchoolTerm();

        if(!$schoolterm){
            $schoolterm = SchoolTerm::getLatest();
        }
    

        if(!$schoolterm){
            Session::flash('alert-warning', 'Não foi encontrado um periodo letivo.');
            return back();
        }

        $selections = Selection::where("sitatl", "Ativo")->whereHas("requisition.instructor", function($query){
                            $query->where("codpes",Auth::user()->codpes);
                        })->whereHas("schoolclass", function($query)use($schoolterm){
                            $query->whereBelongsTo($schoolterm);
                        })->get()->sortBy("student.nompes");

        return view("frequencies.index", compact(["schoolterm","selections"]));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreFrequencyRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreFrequencyRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Frequency  $frequency
     * @return \Illuminate\Http\Response
     */
    public function show(SchoolClass $schoolclass, Student $tutor, Request $request)
    {
        if($request->has("signature")){
            if(!$request->hasValidSignature()){
                abort(403);
            }
        }elseif(Auth::check()){
            if(!$schoolclass->isInstructor(Auth::user()->codpes)){
                Session::flash("alert-warning", "Você não ministra esta disciplina.");
                return back();
            }elseif($schoolclass->selections()->whereHas("student", function($query)use($tutor){$query->where("codpes", $tutor->codpes);})->get()->isEmpty()){
                Session::flash("alert-warning", "Este monitor não pertence a disciplina ".$schoolclass->coddis.".");
                return back();
            }
        }else{
            abort(403);
        }

        return view('frequencies.show', [
            'monitor' => $tutor,
            'turma' => $schoolclass,
            'signature' =>$request->signature,
        ]);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Frequency  $frequency
     * @return \Illuminate\Http\Response
     */
    public function edit(Frequency $frequency)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateFrequencyRequest  $request
     * @param  \App\Models\Frequency  $frequency
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateFrequencyRequest $request, Frequency $frequency)
    {
        if($request->has("signature")){
            if(!$request->hasValidSignature()){
                abort(403);
            }
        }elseif(Auth::check()){
            if(!$schoolclass->isInstructor(Auth::user()->codpes)){
                abort(403);
            }
        }else{
            abort(403);
        }

        $selection = Selection::whereBelongsTo($frequency->schoolclass)->whereBelongsTo($frequency->student)->first();

        if($selection->sitatl != "Ativo"){
            Session::flash('alert-warning', 'Esta monitoria encontra-se com status '.$selection->sitatl.'.');
            return back();  
        }
        
        $meses = [1=>"janeiro", 2=>"fevereiro", 3=>"março", 4=>"abril", 5=>"maio", 6=>"junho", 7=>"julho", 8=>"agosto", 9=>"setembro", 10=>"outubro", 11=>"novembro", 12=>"dezembro"];

        if($frequency->month>date("m") or ($frequency->month==date("m") and date("d")<20)){
            Session::flash('alert-warning', 'A frequência de '.$meses[$frequency->month].' só será liberada a partir do dia 20/'.$frequency->month.'.');
            return back();
        }

        $frequency->registered  = !$frequency->registered;
        $frequency->save();
        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Frequency  $frequency
     * @return \Illuminate\Http\Response
     */
    public function destroy(Frequency $frequency)
    {
        //
    }
}
