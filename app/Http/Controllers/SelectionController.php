<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSelectionRequest;
use App\Http\Requests\UpdateSelectionRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\Selection;
use App\Models\Instructor;
use App\Models\Requisition;
use App\Models\SchoolTerm;
use App\Models\SchoolClass;
use App\Models\Enrollment;

class SelectionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(!Gate::allows('Selecionar monitor')){
            abort(403);
        }

        $periodoLetivo = SchoolTerm::getOpenSchoolTerm();

        if(Auth::user()->hasRole('Membro Comissão')){
            $docente = Instructor::where(['codpes'=>Auth::user()->codpes])->first();

            $departamento = $docente->department;

            $solicitacoes = Requisition::whereHas('schoolclass', function($q) use($departamento, $periodoLetivo){
                return $q->whereBelongsTo($departamento)->whereBelongsTo($periodoLetivo);})->get();
        }elseif(Auth::user()->hasRole(['Secretaria', 'Administrador'])){
            $solicitacoes = Requisition::whereHas('schoolclass', function($q) use($periodoLetivo){
                return $q->whereBelongsTo($periodoLetivo);})->get();
        }

        return view('selections.index', compact('solicitacoes'));
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
     * @param  \App\Http\Requests\StoreSelectionRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreSelectionRequest $request)
    {
        if(!Gate::allows('Selecionar monitor')){
            abort(403);
        }

        $validated = $request->validated();

        $inscricao = Enrollment::find($validated['enrollment_id']);

        $validated['student_id'] = $inscricao->student->id;
        $validated['school_class_id'] = $inscricao->schoolclass->id;
        $validated['requisition_id'] = $inscricao->schoolclass->requisition->id;
        $validated['codpescad'] = Auth::user()->codpes;

        if(Auth::user()->hasRole('Membro Comissão')){
            $docente = Instructor::where(['codpes'=>Auth::user()->codpes])->first();
            if($inscricao->schoolclass->department == $docente->department){
                $selecao = Selection::firstOrCreate($validated);
            }else{
                abort(403);
            }
        }elseif(Auth::user()->hasRole(['Secretaria', 'Administrador'])){
            $selecao = Selection::firstOrCreate($validated);
        }else{
            abort(403);
        }


        return back();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Selection  $selection
     * @return \Illuminate\Http\Response
     */
    public function show(Selection $selection)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Selection  $selection
     * @return \Illuminate\Http\Response
     */
    public function edit(Selection $selection)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateSelectionRequest  $request
     * @param  \App\Models\Selection  $selection
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateSelectionRequest $request, Selection $selection)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Selection  $selection
     * @return \Illuminate\Http\Response
     */
    public function destroy(Selection $selection)
    {
        if(!Gate::allows('Preterir monitor')){
            abort(403);
        }

        if(Auth::user()->hasRole('Membro Comissão')){
            $docente = Instructor::where(['codpes'=>Auth::user()->codpes])->first();
            if($selection->schoolclass->department == $docente->department){
                $selection->delete();
            }else{
                abort(403);
            }
        }elseif(Auth::user()->hasRole(['Secretaria', 'Administrador'])){
            $selection->delete();
        }else{
            abort(403);
        }
        
        return back();
    }

    public function enrollments(SchoolClass $schoolclass)
    {
        if(!Gate::allows('Selecionar monitor')){
            abort(403);
        }

        $turma = $schoolclass;

        $inscricoes = $schoolclass->enrollments()->whereHas('selection')
            ->union(
                $schoolclass->enrollments()->whereDoesntHave('selection')
                            ->whereHas('student.recommendations.requisition', function ($query) use ($turma){
                                return $query->whereBelongsTo($turma);
                            }))
            ->union(
                $schoolclass->enrollments()->whereDoesnthave('selection')
                            ->whereDoesntHave('student.recommendations.requisition', function ($query) use ($turma){
                                return $query->whereBelongsTo($turma);
                            }))->get();

        return view('selections.enrollments', compact(['turma', 'inscricoes']));
    }
}
