<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Requests\IndexStudentRequest;
use App\Models\Student;
use App\Models\User;
use Auth;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(IndexStudentRequest $request)
    {
        $validated = $request->validated();
        
        if($request->expectsJson()){
            if(array_key_exists('codpes', $validated)){
                $codpes = $validated['codpes'];

                $estudante = Student::getFromReplicadoByCodpes($codpes);
                if($estudante){
                    return response()->json($estudante);
                }
            }elseif(array_key_exists('nompes', $validated)){
                $nompes = $validated['nompes'];

                $estudante = Student::getFromReplicadoByNompes($nompes);
                if($estudante){
                    return response()->json($estudante);
                }
            }

            return response()->json("");
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if(!Auth::user()->hasRole('Aluno')){
            abort(403);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreStudentRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreStudentRequest $request)
    {
        if(!Auth::user()->hasRole('Aluno')){
            abort(403);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Http\Response
     */
    public function show(Student $student)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Http\Response
     */
    public function edit(Student $student)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateStudentRequest  $request
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateStudentRequest $request, Student $student)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Http\Response
     */
    public function destroy(Student $student)
    {
        //
    }
}
