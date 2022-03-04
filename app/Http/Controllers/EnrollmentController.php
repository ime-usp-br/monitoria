<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Requests\UpdateEnrollmentRequest;
use App\Http\Requests\CreateEnrollmentRequest;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Group;
use App\Models\SchoolTerm;
use Auth;
use Session;

class EnrollmentController extends Controller
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
    public function create(CreateEnrollmentRequest $request)
    {
        if(!Auth::user()->hasRole('Aluno')){
            abort(403);
        }

        $validated = $request->validated();

        $periodoAbertoAInscrições = SchoolTerm::where('start_date_student_registration', '<=', now())
                                              ->where('end_date_student_registration', '>=', now())->first();
        
        if(!$periodoAbertoAInscrições){
            Session::flash('alert-warning', 'Período de inscrições encerrado');
            return redirect('/');
        } 
        
        $estudante = Student::where(['codpes'=>Auth::user()->codpes])->first();

        if(count($estudante->enrollments)>=4){
            Session::flash('alert-warning', 'Você excedeu o número máximo de inscrições');
            return redirect('/');
        }

        $turma = Group::find($validated['group_id']);

        return view('enrollments.create', compact(['turma', 'estudante']));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreEnrollmentRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreEnrollmentRequest $request)
    {
        if(!Auth::user()->hasRole('Aluno')){
            abort(403);
        }

        $validated = $request->validated();

        $validated['student_id'] =Student::where(['codpes'=>Auth::user()->codpes])->first()->id;
        
        $inscricao = Enrollment::create($validated);

        return redirect('/enrollments/groups');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Enrollment  $enrollment
     * @return \Illuminate\Http\Response
     */
    public function show(Enrollment $enrollment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Enrollment  $enrollmentreturn redirect('/enrollments/groups');
     * @return \Illuminate\Http\Response
     */
    public function edit(Enrollment $enrollment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateEnrollmentRequest  $request
     * @param  \App\Models\Enrollment  $enrollment
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateEnrollmentRequest $request, Enrollment $enrollment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Enrollment  $enrollment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Enrollment $enrollment)
    {
        //
    }

    public function showGroupsInCurrentSchoolTerm()
    {
        if(!Auth::user()->hasRole('Aluno')){
            abort(403);
        }
        $periodoAbertoAInscrições = SchoolTerm::where('start_date_student_registration', '<=', now())
                                              ->where('end_date_student_registration', '>=', now())->first();
        
        if(!$periodoAbertoAInscrições){
            Session::flash('alert-warning', 'Período de inscrições encerrado');
            return redirect('/');
        }                                

        $estudante = Student::where(['codpes'=>Auth::user()->codpes])->first();
        $turmas = Group::where(['school_term_id'=>$periodoAbertoAInscrições->id])->get();

        return view('enrollments.groups', compact(['turmas', 'estudante']));
    }
}
