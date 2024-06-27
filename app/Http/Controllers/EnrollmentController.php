<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Requests\UpdateEnrollmentRequest;
use App\Http\Requests\CreateEnrollmentRequest;
use App\Http\Requests\ShowAllEnrollmentRequest;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\SchoolTerm;
use App\Models\Scholarship;
use App\Models\Selection;
use Illuminate\Support\Facades\Gate;
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
        if(!Auth::user()){
            Session::flash('alert-warning', 'Antes de se inscrever você precisa fazer login');
            return redirect('/');
        }elseif(!Auth::user()->hasRole('Aluno')){
            abort(403);
        }elseif(!SchoolTerm::isEnrollmentPeriod()){
            Session::flash('alert-warning', 'Período de inscrições encerrado');
            return redirect('/');
        }elseif(!SchoolTerm::getOpenSchoolTerm()){
            Session::flash('alert-warning', 'Período letivo fechado');
            return redirect('/');
        }elseif(SchoolTerm::getOpenSchoolTerm()->id != SchoolTerm::getSchoolTermInEnrollmentPeriod()->id){
            Session::flash('alert-warning', 'Período letivo aberto é diferente do periodo letivo com inscrições abertas, favor informar a secretaria de monitoria.');
            return redirect('/');
        }                                 

        $estudante = Student::where(['codpes'=>Auth::user()->codpes])->first();

        if(!$estudante->getSchoolRecordFromOpenSchoolTerm()){
            return redirect(route('schoolRecords.create'));
        }
        
        $turmas = SchoolClass::whereInEnrollmentPeriod()->whereHas('enrollments', function($query) use ($estudante){
                    return $query->where(['student_id'=>$estudante->id]);
                })->get();
        $turmas = $turmas->merge(SchoolClass::whereInEnrollmentPeriod()->whereDoesntHave('enrollments', function($query) use ($estudante){
                    return $query->where(['student_id'=>$estudante->id]);
                })->orderBy("coddis")->get());

        return view('enrollments.index', compact(['turmas', 'estudante']));
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
        }elseif(!SchoolTerm::isEnrollmentPeriod()){
            Session::flash('alert-warning', 'Período de inscrições encerrado');
            return redirect('/');
        } 

        $validated = $request->validated();
        
        $estudante = Student::where(['codpes'=>Auth::user()->codpes])->first();

        $schoolterm = SchoolTerm::getSchoolTermInEnrollmentPeriod();

        if(SchoolClass::whereBelongsTo($schoolterm)->whereHas("enrollments", 
                    function($query)use($estudante){$query->whereBelongsTo($estudante);})
                ->pluck("coddis")->unique()->count()>=$schoolterm->max_enrollments){
            Session::flash('alert-warning', 'Você excedeu o número máximo de inscrições');
            return redirect('/enrollments');
        }

        $turma = SchoolClass::find($validated['school_class_id']);

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
        }elseif(!SchoolTerm::isEnrollmentPeriod()){
            Session::flash('alert-warning', 'Período de inscrições encerrado');
            return redirect('/');
        } 

        $validated = $request->validated();

        $validated['student_id'] = Student::where(['codpes'=>Auth::user()->codpes])->first()->id;

        $scholarships = array_key_exists('scholarships', $validated) ? $validated['scholarships'] : [];
        unset($validated['scholarships']);

        $schoolterm = SchoolTerm::getSchoolTermInEnrollmentPeriod();
        
        foreach(SchoolClass::whereBelongsTo($schoolterm)->where("coddis",SchoolClass::find($validated["school_class_id"])->coddis)->get() as $schoolclass){
            $validated["school_class_id"] = $schoolclass->id;

            $enrollment = Enrollment::create($validated);

            foreach($scholarships as $scholarship_id){
                $enrollment->others_scholarships()->attach(Scholarship::find($scholarship_id));
            }
        }


        return redirect('/enrollments');
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
     * @param  \App\Models\Enrollment  $enrollment
     * @return \Illuminate\Http\Response
     */
    public function edit(Enrollment $enrollment)
    {
        if(!Auth::user()->hasRole('Aluno')){
            abort(403);
        }elseif(!SchoolTerm::isEnrollmentPeriod()){
            Session::flash('alert-warning', 'Período de inscrições encerrado');
            return redirect('/');
        } 

        $inscricao = $enrollment;

        $estudante = $enrollment->student;

        return view('enrollments.edit', compact(['inscricao', 'estudante']));
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
        if(!Auth::user()->hasRole('Aluno')){
            abort(403);
        }elseif(!SchoolTerm::isEnrollmentPeriod()){
            Session::flash('alert-warning', 'Período de inscrições encerrado');
            return redirect('/');
        } 

        $validated = $request->validated();

        $validated['voluntario'] = isset($validated['voluntario']) ? 1 : 0;

        $validated['disponibilidade_diurno'] = isset($validated['disponibilidade_diurno']) ? 1 : 0;

        $validated['disponibilidade_noturno'] = isset($validated['disponibilidade_noturno']) ? 1 : 0;

        $scholarships = array_key_exists('scholarships', $validated) ? $validated['scholarships'] : [];
        unset($validated['scholarships']);

        $schoolterm = SchoolTerm::getSchoolTermInEnrollmentPeriod();

        $enrollments = Enrollment::whereHas("schoolclass", function($query)use($enrollment,$schoolterm){
            $query->whereBelongsTo($schoolterm)->where("coddis",$enrollment->schoolclass->coddis);})
        ->whereHas("student", function($query)use($enrollment){
            $query->where("id", $enrollment->student->id);})->get();

        foreach($enrollments as $e){
            $e->others_scholarships()->detach();

            foreach($scholarships as $scholarship_id){
                $e->others_scholarships()->attach(Scholarship::find($scholarship_id));
            }

            $validated["school_class_id"] = $e->schoolclass->id;

            $e->update($validated);
        }

        return redirect('/enrollments');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Enrollment  $enrollment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Enrollment $enrollment)
    {
        if(!Auth::user()->hasRole('Aluno')){
            abort(403);
        }elseif(!SchoolTerm::isEnrollmentPeriod()){
            Session::flash('alert-warning', 'Período de inscrições encerrado');
            return redirect('/');
        } 

        $enrollments = Enrollment::whereHas("schoolclass", function($query)use($enrollment){
            $query->whereBelongsTo($enrollment->schoolclass->schoolterm)
                ->where("coddis",$enrollment->schoolclass->coddis);
        })->whereHas("student", function($query)use($enrollment){
            $query->where("id", $enrollment->student->id);})->get();
        
        $hasSelection = Selection::whereHas("enrollment", function($query)use($enrollments){
            $query->whereIn("id", $enrollments->pluck("id")->toArray());
        })->exists();

        if($hasSelection){
            Session::flash('alert-warning', 'Você foi selecionado como monitor de uma turma dessa disciplina. 
                Caso queira desistir comunique a comissão de monitoria.');
            return back();
        }

        Enrollment::whereHas("schoolclass", function($query)use($enrollment){
                $query->whereBelongsTo($enrollment->schoolclass->schoolterm)
                    ->where("coddis",$enrollment->schoolclass->coddis);
            })->whereHas("student", function($query)use($enrollment){
                $query->where("id", $enrollment->student->id);})->delete();

        return redirect('/enrollments');
    }

    public function showAll(ShowAllEnrollmentRequest $request)
    {
        if(!Gate::allows('visualizar todos inscritos')){
            abort(403);
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

        $alunos = Student::whereHas("enrollments.schoolclass", function($query)use($schoolterm){
            $query->whereBelongsTo($schoolterm);})->get()->sortBy("nompes");

        return view('enrollments.showAll', compact(['alunos', 'schoolterm']));
    }
}
