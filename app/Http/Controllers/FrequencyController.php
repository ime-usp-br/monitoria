<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Gate;
use App\Http\Requests\StoreFrequencyRequest;
use App\Http\Requests\UpdateFrequencyRequest;
use App\Models\Frequency;
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
        //
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
    public function show(Frequency $frequency)
    {
        //
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

        if($frequency->month>date("m")){
            Session::flash('alert-warning', 'Você ainda não pode registrar a frequência do mês '.$frequency->month.'.');
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
