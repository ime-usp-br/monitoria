<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSelfEvaluationRequest;
use App\Http\Requests\UpdateSelfEvaluationRequest;
use App\Http\Requests\IndexSelfEvaluationRequest;
use App\Http\Requests\CreateSelfEvaluationRequest;
use Illuminate\Support\Facades\Hash;
use App\Models\SelfEvaluation;
use App\Models\SchoolTerm;
use App\Models\Selection;
use App\Models\Student;
use Auth;
use Gate;
use Session;

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

    public function studentIndex()
    {
        if(Auth::check()){
            if(!Auth::user()->hasRole(["Aluno"])){
                abort(403);
            }
        }else{
            return redirect("login");
        }

        $selections = Selection::whereBelongsTo(Student::where("codpes", Auth::user()->codpes)->first())
                                ->whereHas("selfevaluation")->union(
                                        Selection::whereBelongsTo(Student::where("codpes", Auth::user()->codpes)->first())
                                                    ->whereHas("schoolclass", function($query){
                                                        $query->whereHas("schoolterm", function($query2){
                                                            $query2->where("id", SchoolTerm::getSchoolTermInEvaluationPeriod()->id ?? "");
                                                        });
                                                    })
                                    )
                                ->get()->sortBy(["schoolclass.schoolterm.year", "schoolclass.schoolterm.period"])->reverse();

        return view("selfevaluations.studentIndex", compact("selections"));

    }



    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(CreateSelfEvaluationRequest $request)
    {
        if($request->has("signature")){
            if(!$request->hasValidSignature()){
                abort(403);
            }
        }elseif(Auth::check()){
            if(!Auth::user()->hasRole(["Aluno"])){
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
        }elseif(!$selection->schoolclass->schoolterm->isInEvaluationPeriod()){
            Session::flash('alert-warning', 'Período de avaliação encerrado.');
            return redirect("/");
        }elseif(Auth::check()){
            if($selection->student->codpes != Auth::user()->codpes){
                Session::flash('alert-warning', 'Essa monitoria não pertence a você.');
                return redirect("/");
            }
        }

        return view("selfevaluations.create", compact("selection"));
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreSelfEvaluationRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreSelfEvaluationRequest $request)
    {
        $validated = $request->validated();

        $selection = Selection::find($validated["selection_id"]);

        if(!$selection){
            Session::flash('alert-warning', 'Monitoria não encontrada.');
            return redirect("/");
        }elseif(Auth::check()){
            if($selection->student->codpes != Auth::user()->codpes){
                Session::flash('alert-warning', 'Essa monitoria não pertence a você.');
                return redirect("/");
            }
        }elseif(!Hash::check(json_encode($selection->toArray()),$validated["selection_hash"])){
            Session::flash('alert-warning', 'Essa monitoria não pertence a você.');
            return redirect("/");
        }

        SelfEvaluation::updateOrCreate(["selection_id"=>$selection->id],$validated);

        Session::flash('alert-success', 'Avaliação cadastrada com sucesso.');

        if(!Auth::check()){
            return redirect("/");

        }

        return redirect(route("selfevaluations.studentIndex"));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\SelfEvaluation  $selfEvaluation
     * @return \Illuminate\Http\Response
     */
    public function show(SelfEvaluation $selfevaluation)
    {
        if(Auth::check()){
            if(Auth::user()->hasRole("Aluno")){
                if($selfevaluation->student->codpes =! Auth::user()->codpes){
                    abort(403);
                }
            }elseif(!Gate::allows('Visualizar auto avaliações')){
                abort(403);
            }
        }else{
            return redirect("login");
        }

        return view("selfevaluations.show", ["se"=>$selfevaluation]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SelfEvaluation  $selfEvaluation
     * @return \Illuminate\Http\Response
     */
    public function edit(SelfEvaluation $selfevaluation)
    {
        if(Auth::check()){
            if(!Auth::user()->hasRole(["Aluno"])){
                abort(403);
            }
        }else{
            return redirect("login");
        }

        if($selfevaluation->student->codpes != Auth::user()->codpes){
            Session::flash('alert-warning', 'Essa monitoria não pertence a você.');
            return redirect("/");
        }
        
        return view("selfevaluations.edit", ["selection"=>$selfevaluation->selection]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateSelfEvaluationRequest  $request
     * @param  \App\Models\SelfEvaluation  $selfEvaluation
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateSelfEvaluationRequest $request, SelfEvaluation $selfevaluation)
    {
        if(Auth::check()){
            if(!Auth::user()->hasRole(["Aluno"])){
                abort(403);
            }
        }else{
            return redirect("login");
        }

        $validated = $request->validated();

        $selfevaluation->update($validated);
        
        return redirect(route("selfevaluations.studentIndex"));
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
