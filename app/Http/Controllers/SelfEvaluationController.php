<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSelfEvaluationRequest;
use App\Http\Requests\UpdateSelfEvaluationRequest;
use App\Http\Requests\IndexSelfEvaluationRequest;
use App\Models\SelfEvaluation;
use App\Models\SchoolTerm;
use Auth;
use Gate;

class SelfEvaluationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(IndexSelfEvaluationRequest $request)
    {
        if(Auth::check()){
            if(!Gate::allows('Visualizar auto avaliações')){
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

        $ses = SelfEvaluation::whereHas("schoolterm", function($query)use($schoolterm){
            $query->where(["year"=>$schoolterm->year,"period"=>$schoolterm->period]);
        })->get();

        return view("selfevaluations.index", compact(["ses", "schoolterm"]));
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
     * @param  \App\Http\Requests\StoreSelfEvaluationRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreSelfEvaluationRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\SelfEvaluation  $selfEvaluation
     * @return \Illuminate\Http\Response
     */
    public function show(SelfEvaluation $selfevaluation)
    {
        return view("selfevaluations.show", ["se"=>$selfevaluation]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SelfEvaluation  $selfEvaluation
     * @return \Illuminate\Http\Response
     */
    public function edit(SelfEvaluation $selfEvaluation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateSelfEvaluationRequest  $request
     * @param  \App\Models\SelfEvaluation  $selfEvaluation
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateSelfEvaluationRequest $request, SelfEvaluation $selfEvaluation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SelfEvaluation  $selfEvaluation
     * @return \Illuminate\Http\Response
     */
    public function destroy(SelfEvaluation $selfEvaluation)
    {
        //
    }
}
