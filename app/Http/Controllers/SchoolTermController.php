<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSchoolTermRequest;
use App\Http\Requests\UpdateSchoolTermRequest;
use App\Http\Requests\DownloadPublicNoticeRequest;
use Illuminate\Support\Facades\Storage;
use App\Models\SchoolTerm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Session;

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

        $periodos = SchoolTerm::all()->sortBy(["year","period"])->reverse();

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

        if($validated['status'] == 'Aberto'){
            $estadoEmAberto = SchoolTerm::where('status', 'Aberto')->first();
            if($estadoEmAberto){
                Session::flash("alert-warning", "O período letivo {$estadoEmAberto->period} de {$estadoEmAberto->year} consta com estado em aberto, o sistema permite apenas um período letivo com 
                estado em aberto por vez.");
                return back();
            }
        }

        $validated['public_notice_file_path'] = $validated['public_notice']->store($validated['year'] . $validated['period'][0]);

        $periodo = SchoolTerm::updateOrCreate(['year'=>$validated['year'], 'period'=>$validated['period']],$validated);

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

        if($validated['status'] == 'Aberto'){
            $estadoEmAberto = SchoolTerm::where('status', 'Aberto')->where('id', '!=', $schoolterm->id)->first();
            if($estadoEmAberto){
                Session::flash("alert-warning", "O período letivo {$estadoEmAberto->period} de {$estadoEmAberto->year} consta com estado em aberto, o sistema permite apenas um período letivo com 
                estado em aberto por vez.");
                return back();
            }
        }

        if(in_array("public_notice",array_keys($validated))){
            $validated['public_notice_file_path'] = $validated['public_notice']->store($validated['year'] . $validated['period'][0]);
        }

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

    public function download(DownloadPublicNoticeRequest $request)
    {
        $validated = $request->validated();

        return Storage::download($validated['path'], 'edital_monitoria.pdf');
    }
}
