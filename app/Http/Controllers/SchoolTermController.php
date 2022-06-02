<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSchoolTermRequest;
use App\Http\Requests\UpdateSchoolTermRequest;
use App\Models\SchoolTerm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class SchoolTermController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(!Gate::allows('visualizar periodo letivo')){
            abort(403);
        }

        $periodos = SchoolTerm::orderBy('year')
        ->orderBy('period')->get();

        return view('schoolterms.index', compact('periodos'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if(!Gate::allows('criar periodo letivo')){
            abort(403);
        }

        $periodo = new SchoolTerm;

        return view('schoolterms.create', compact('periodo'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreSchoolTermRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreSchoolTermRequest $request)
    {
        if(!Gate::allows('criar periodo letivo')){
            abort(403);
        }

        $validated = $request->validated();
        $periodo = SchoolTerm::create($validated);

        return redirect('/schoolterms');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\SchoolTerm  $schoolterm
     * @return \Illuminate\Http\Response
     */
    public function show(SchoolTerm $schoolterm)
    {
        if(!Gate::allows('visualizar periodo letivo')){
            abort(403);
        }

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SchoolTerm  $schoolterm
     * @return \Illuminate\Http\Response
     */
    public function edit(SchoolTerm $schoolterm)
    {
        if(!Gate::allows('editar periodo letivo')){
            abort(403);
        }

        $periodo = $schoolterm;

        return view('schoolterms.edit', compact('periodo'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateSchoolTermRequest  $request
     * @param  \App\Models\SchoolTerm  $schoolterm
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateSchoolTermRequest $request, SchoolTerm $schoolterm)
    {
        if(!Gate::allows('editar periodo letivo')){
            abort(403);
        }

        $validated = $request->validated();
        $schoolterm->update($validated);

        return redirect('/schoolterms');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SchoolTerm  $schoolterm
     * @return \Illuminate\Http\Response
     */
    public function destroy(SchoolTerm $schoolterm)
    {
        if(!Gate::allows('deletar periodo letivo')){
            abort(403);
        }
    }
}
