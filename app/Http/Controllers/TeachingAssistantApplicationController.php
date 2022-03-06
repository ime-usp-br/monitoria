<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeachingAssistantApplicationRequest;
use App\Http\Requests\UpdateTeachingAssistantApplicationRequest;
use App\Http\Requests\CreateTeachingAssistantApplicationRequest;
use App\Models\TeachingAssistantApplication;
use App\Models\SchoolClass;
use App\Models\SchoolTerm;
use App\Models\Instructor;
use App\Models\Activity;
use Illuminate\Support\Facades\Gate;
use Auth;
use Session;

class TeachingAssistantApplicationController extends Controller
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

        $turmas = SchoolClass::whereHas('instructors', function($query) { 
            $query->where('instructors.codpes', Auth::user()->codpes); 
        })->get();

        $schoolterms = SchoolTerm::all();

        return view('teachingAssistantApplication.index', compact(['turmas', 'schoolterms']));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(CreateTeachingAssistantApplicationRequest $request)
    {
        if(!Gate::allows('criar solicitação de monitor')){
            abort(403);
        }

        $validated = $request->validated();
        $turma = SchoolClass::find($validated['school_class_id']);

        if($turma->isInstructor(Auth::user()->codpes)){
            if($turma->isSchoolTermOpen()){
                return view('teachingAssistantApplication.create', compact('turma'));
            }else{
                Session::flash('alert-warning', 'Período de solicitação de monitores encerrado');
                return redirect('/requestAssistant');
            }
        }else{
            abort(403);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreTeachingAssistantApplicationRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreTeachingAssistantApplicationRequest $request)
    {
        if(!Gate::allows('criar solicitação de monitor')){
            abort(403);
        }

        $validated = $request->validated();

        $activities = $validated['activities'];
        unset($validated['activities']);

        $validated['instructor_id'] = Instructor::where(['codpes'=>Auth::user()->codpes])->first()->id;

        $requestAssistant = TeachingAssistantApplication::create($validated);

        foreach($activities as $act){
            $requestAssistant->activities()->attach(Activity::firstOrCreate(['description'=>$act]));
        }

        return redirect('/requestAssistant');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\TeachingAssistantApplication  $teachingAssistantApplication
     * @return \Illuminate\Http\Response
     */
    public function show(TeachingAssistantApplication $teachingAssistantApplication)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\TeachingAssistantApplication  $teachingAssistantApplication
     * @return \Illuminate\Http\Response
     */
    public function edit(TeachingAssistantApplication $requestAssistant)
    {
        if(!Gate::allows('editar solicitação de monitor')){
            abort(403);
        }

        $turma = $requestAssistant->schoolclass;
        if($turma->isInstructor(Auth::user()->codpes)){
            if($turma->isSchoolTermOpen()){
                return view('teachingAssistantApplication.edit', compact('turma'));
            }else{
                Session::flash('alert-warning', 'Período de solicitação de monitores encerrado');
                return redirect('/requestAssistant');
            }
        }else{
            abort(403);
        }
        
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateTeachingAssistantApplicationRequest  $request
     * @param  \App\Models\TeachingAssistantApplication  $teachingAssistantApplication
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateTeachingAssistantApplicationRequest $request, TeachingAssistantApplication $requestAssistant)
    {
        if(!Gate::allows('editar solicitação de monitor')){
            abort(403);
        }

        $validated = $request->validated();

        $requestAssistant->activities()->detach();
        foreach($validated['activities'] as $act){
            $requestAssistant->activities()->attach(Activity::firstOrCreate(['description'=>$act]));
        }

        $requestAssistant->update($validated);

        return redirect('/requestAssistant');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TeachingAssistantApplication  $teachingAssistantApplication
     * @return \Illuminate\Http\Response
     */
    public function destroy(TeachingAssistantApplication $teachingAssistantApplication)
    {
        //
    }
}
