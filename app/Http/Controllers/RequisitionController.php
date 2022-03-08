<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRequisitionRequest;
use App\Http\Requests\UpdateRequisitionRequest;
use App\Http\Requests\CreateRequisitionRequest;
use App\Models\Requisition;
use App\Models\SchoolClass;
use App\Models\SchoolTerm;
use App\Models\Instructor;
use App\Models\Activity;
use App\Models\Recommendation;
use App\Models\Student;
use Illuminate\Support\Facades\Gate;
use Auth;
use Session;

class RequisitionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(!Gate::allows('visualizar solicitação de monitor')){
            abort(403);
        }

        $turmas = SchoolClass::whereInRequisitionPeriod()->whereHas('instructors', function($query) { 
            $query->where('instructors.codpes', Auth::user()->codpes); 
        })->get();

        $schoolterms = SchoolTerm::all();

        return view('requisitions.index', compact(['turmas', 'schoolterms']));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(CreateRequisitionRequest $request)
    {
        if(!Gate::allows('criar solicitação de monitor')){
            abort(403);
        }

        $validated = $request->validated();
        $turma = SchoolClass::find($validated['school_class_id']);

        if($turma->isInstructor(Auth::user()->codpes)){
            if($turma->isSchoolTermOpen()){
                return view('requisitions.create', compact('turma'));
            }else{
                Session::flash('alert-warning', 'Período de solicitação de monitores encerrado');
                return redirect('/requisitions');
            }
        }else{
            abort(403);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreRequisitionRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequisitionRequest $request)
    {
        if(!Gate::allows('criar solicitação de monitor')){
            abort(403);
        }

        $validated = $request->validated();

        $activities = $validated['activities'];
        unset($validated['activities']);

        $recommendations = array_key_exists('recommendations', $validated) ? $validated['recommendations'] : [];
        unset($validated['recommendations']);

        $validated['instructor_id'] = Instructor::where(['codpes'=>Auth::user()->codpes])->first()->id;

        $requisition = Requisition::create($validated);

        foreach($activities as $act){
            $requisition->activities()->attach(Activity::firstOrCreate(['description'=>$act]));
        }

        foreach($recommendations as $recommendation){
            Recommendation::create([
                'student_id'=>Student::firstOrCreate(Student::getFromReplicadoByCodpes($recommendation['codpes']))->id,
                'requisition_id'=>$requisition->id
            ]);
        }

        return redirect('/requisitions');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Requisition  $requisition
     * @return \Illuminate\Http\Response
     */
    public function show(Requisition $requisition)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Requisition  $requisition
     * @return \Illuminate\Http\Response
     */
    public function edit(Requisition $requisition)
    {
        if(!Gate::allows('editar solicitação de monitor')){
            abort(403);
        }

        $turma = $requisition->schoolclass;
        if($turma->isInstructor(Auth::user()->codpes)){
            if($turma->isSchoolTermOpen()){
                return view('requisitions.edit', compact('turma'));
            }else{
                Session::flash('alert-warning', 'Período de solicitação de monitores encerrado');
                return redirect('/requisitions');
            }
        }else{
            abort(403);
        }
        
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateRequisitionRequest  $request
     * @param  \App\Models\Requisition  $requisition
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequisitionRequest $request, Requisition $requisition)
    {
        if(!Gate::allows('editar solicitação de monitor')){
            abort(403);
        }

        $validated = $request->validated();

        $activities = $validated['activities'];
        unset($validated['activities']);

        $recommendations = array_key_exists('recommendations', $validated) ? $validated['recommendations'] : [];
        unset($validated['recommendations']);

        $requisition->activities()->detach();
        foreach($activities as $act){
            $requisition->activities()->attach(Activity::firstOrCreate(['description'=>$act]));
        }

        $requisition->recommendations()->delete();
        foreach($recommendations as $recommendation){
            Recommendation::create([
                'student_id'=>Student::firstOrCreate(Student::getFromReplicadoByCodpes($recommendation['codpes']))->id,
                'requisition_id'=>$requisition->id
            ]);
        }

        $requisition->update($validated);

        return redirect('/requisitions');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Requisition  $requisition
     * @return \Illuminate\Http\Response
     */
    public function destroy(Requisition $requisition)
    {
        //
    }
}
