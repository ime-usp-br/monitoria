<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInstructorEvaluationRequest;
use App\Http\Requests\UpdateInstructorEvaluationRequest;
use App\Http\Requests\IndexInstructorEvaluationRequest;
use App\Http\Requests\CreateInstructorEvaluationRequest;
use Illuminate\Support\Facades\Hash;
use App\Models\InstructorEvaluation;
use App\Models\SchoolTerm;
use App\Models\Selection;
use Auth;
use Gate;
use Session;

class InstructorEvaluationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(IndexInstructorEvaluationRequest $request)
    {
        if(Auth::check()){
            if(!Gate::allows('Visualizar avaliações dos docentes')){
                abort(403);
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

        $ies = InstructorEvaluation::whereHas("schoolterm", function($query)use($schoolterm){
            $query->where(["year"=>$schoolterm->year,"period"=>$schoolterm->period]);
        })->get()->sortBy("instructor.nompes");

        return view("instructorevaluations.index", compact(["ies", "schoolterm"]));
    }

    public function instructorIndex()
    {
        if(Auth::check()){
            if(!Auth::user()->hasRole("Docente")){
                abort(403);
            }
        }else{
            return redirect("login");
        }

        $selections = Selection::whereHas("requisition.instructor", function($query){
                            $query->where("codpes",Auth::user()->codpes);
                        })->whereHas("instructorevaluation")->union(
                            Selection::whereHas("requisition.instructor", function($query){
                                $query->where("codpes",Auth::user()->codpes);
                            })->whereHas("schoolclass.schoolterm", function($query){
                                $query->where("id", SchoolTerm::getSchoolTermInEvaluationPeriod()->id ?? "");
                            })
                        )->get()->sortBy(["schoolclass.schoolterm.year", "schoolclass.schoolterm.period"])->reverse();

        return view("instructorevaluations.instructorIndex",compact("selections"));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(CreateInstructorEvaluationRequest $request)
    {
        if($request->has("signature")){
            if(!$request->hasValidSignature()){
                abort(403);
            }
        }elseif(Auth::check()){
            if(!Auth::user()->hasRole(["Docente"])){
                abort(403);
            }
        }else{
            return redirect("login");
        }

        $validated = $request->validated();

        $selection = Selection::find($validated["selectionID"]);

        if(!$selection){
            Session::flash('alert-warning', 'Monitoria não encontrada.');
            return redirect("/");
        }elseif($selection->schoolclass->schoolterm->evaluation_period != "Aberto"){
            Session::flash('alert-warning', 'Período de avaliação encerrado.');
            return redirect("/");
        }elseif(Auth::check()){
            if($selection->requisition->instructor->codpes != Auth::user()->codpes){
                Session::flash('alert-warning', 'Esse monitor não esta sob sua responsabilidade.');
                return redirect("/");
            }
        }

        return view("instructorevaluations.create", compact("selection"));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreInstructorEvaluationRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreInstructorEvaluationRequest $request)
    {
        $validated = $request->validated();

        $selection = Selection::find($validated["selection_id"]);

        if(!$selection){
            Session::flash('alert-warning', 'Monitoria não encontrada.');
            return redirect("/");
        }elseif(Auth::check()){
            if($selection->requisition->instructor->codpes != Auth::user()->codpes){
                Session::flash('alert-warning', 'Esse monitor não esta sob sua responsabilidade.');
                return redirect("/");
            }
        }elseif(!Hash::check(json_encode($selection->toArray()),$validated["selection_hash"])){
            Session::flash('alert-warning', 'Essa monitoria não pertence a você.');
            return redirect("/");
        }

        InstructorEvaluation::updateOrCreate(["selection_id"=>$selection->id],$validated);

        Session::flash('alert-success', 'Avaliação cadastrada com sucesso.');

        if(!Auth::check()){
            return redirect("/");

        }

        return redirect(route("instructorevaluations.instructorIndex"));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\InstructorEvaluation  $instructorEvaluation
     * @return \Illuminate\Http\Response
     */
    public function show(InstructorEvaluation $instructorevaluation)
    {
        if(Auth::check()){
            if(Auth::user()->hasRole("Docente")){
                if($instructorevaluation->instructor->codpes != Auth::user()->codpes){
                    abort(403);
                }
            }elseif(!Gate::allows('Visualizar avaliações dos docentes')){
                abort(403);
            }
        }else{
            return redirect("login");
        }

        return view("instructorevaluations.show", ["ie"=>$instructorevaluation]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\InstructorEvaluation  $instructorEvaluation
     * @return \Illuminate\Http\Response
     */
    public function edit(InstructorEvaluation $instructorevaluation)
    {
        if(Auth::check()){
            if(!Auth::user()->hasRole(["Docente"])){
                abort(403);
            }
        }else{
            return redirect("login");
        }

        if($instructorevaluation->instructor->codpes != Auth::user()->codpes){
            Session::flash('alert-warning', 'Esse monitor não esta sob sua responsabilidade.');
            return redirect("/");
        }
        
        return view("instructorevaluations.edit", ["selection"=>$instructorevaluation->selection]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateInstructorEvaluationRequest  $request
     * @param  \App\Models\InstructorEvaluation  $instructorEvaluation
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateInstructorEvaluationRequest $request, InstructorEvaluation $instructorevaluation)
    {
        if(Auth::check()){
            if(!Auth::user()->hasRole(["Docente"])){
                abort(403);
            }
        }else{
            return redirect("login");
        }

        $validated = $request->validated();

        $instructorevaluation->update($validated);
        
        return redirect(route("instructorevaluations.instructorIndex"));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\InstructorEvaluation  $instructorEvaluation
     * @return \Illuminate\Http\Response
     */
    public function destroy(InstructorEvaluation $instructorEvaluation)
    {
        //
    }
}
