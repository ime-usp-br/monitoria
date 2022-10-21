<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInstructorEvaluationRequest;
use App\Http\Requests\UpdateInstructorEvaluationRequest;
use App\Http\Requests\IndexInstructorEvaluationRequest;
use App\Models\InstructorEvaluation;
use App\Models\SchoolTerm;
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
     * @param  \App\Http\Requests\StoreInstructorEvaluationRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreInstructorEvaluationRequest $request)
    {
        //
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
            if(!Gate::allows('Visualizar avaliações dos docentes')){
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
    public function edit(InstructorEvaluation $instructorEvaluation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateInstructorEvaluationRequest  $request
     * @param  \App\Models\InstructorEvaluation  $instructorEvaluation
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateInstructorEvaluationRequest $request, InstructorEvaluation $instructorEvaluation)
    {
        //
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
