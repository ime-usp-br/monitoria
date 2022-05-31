<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSchoolClassRequest;
use App\Http\Requests\UpdateSchoolClassRequest;
use App\Http\Requests\CreateSchoolClassRequest;
use App\Http\Requests\SearchSchoolClassRequest;
use App\Jobs\ProcessGetSchoolClassesFromReplicado;
use Illuminate\Support\Facades\URL;
use App\Models\SchoolClass;
use App\Models\Instructor;
use App\Models\ClassSchedule;
use App\Models\SchoolTerm;
use App\Models\Department;
use App\Models\Selection;
use App\Models\Student;
use App\Models\Frequency;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Uspdev\Replicado\Pessoa;
use Session;
use Auth;

class SchoolClassController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(!Gate::allows('visualizar turma')){
            abort(403);
        }elseif(Auth::user()->hasRole('Docente')){
            $turmas = SchoolClass::whereHas('instructors', function($query) { 
                $query->where('instructors.codpes', Auth::user()->codpes); 
            })->get();
        }else{
            $turmas = SchoolClass::all();
        }

        $schoolterms = SchoolTerm::all();

        return view('schoolclasses.index', compact(['turmas', 'schoolterms']));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(CreateSchoolClassRequest $request)
    {
        if(!Gate::allows('criar turma')){
            abort(403);
        }

        $turma = new SchoolClass;
        $validated = $request->validated();
        $schoolTerm = SchoolTerm::find($validated["periodoId"]);
        $turma->schoolterm()->associate($schoolTerm);

        foreach(Department::getFromReplicadoByInstitute(env("UNIDADE")) as $department){
            Department::firstOrCreate($department);
        }

        return view('schoolclasses.create', compact('turma'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreSchoolClassRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreSchoolClassRequest $request)
    {
        if(!Gate::allows('criar turma')){
            abort(403);
        }

        $validated = $request->validated();
        $schoolTerm = SchoolTerm::find($validated["periodoId"]);
        $schoolclass = SchoolClass::where(array_intersect_key($validated, array_flip(array('codtur', 'coddis'))))->first();

        if(!$schoolclass){
            $schoolclass = new SchoolClass;

            $schoolclass->fill($validated);

            $schoolclass->schoolterm()->associate($schoolTerm);
            $schoolclass->save();

            if(array_key_exists('instrutores', $validated)){
                foreach($validated['instrutores'] as $instructor){
                    $schoolclass->instructors()->attach(Instructor::firstOrCreate(Instructor::getFromReplicadoByCodpes($instructor['codpes'])));
                }
            }   

            if(array_key_exists('horarios', $validated)){
                foreach($validated['horarios'] as $classSchedule){
                    $schoolclass->classschedules()->attach(ClassSchedule::firstOrCreate($classSchedule));
                }
            }
            $schoolclass->save();
        }else{
            Session::flash("alert-warning", "Já existe uma turma cadastrada com esse código da turma e código da disciplina");
            return back();
        }

        return redirect('/schoolclasses');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\SchoolClass  $schoolclass
     * @return \Illuminate\Http\Response
     */
    public function show(SchoolClass $schoolclass)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SchoolClass  $schoolclass
     * @return \Illuminate\Http\Response
     */
    public function edit(SchoolClass $schoolclass)
    {
        if(!Gate::allows('editar turma')){
            abort(403);
        }

        $turma = $schoolclass;

        return view('schoolclasses.edit', compact('turma'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateSchoolClassRequest  $request
     * @param  \App\Models\SchoolClass  $schoolclass
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateSchoolClassRequest $request, SchoolClass $schoolclass)
    {
        if(!Gate::allows('editar turma')){
            abort(403);
        }

        $validated = $request->validated();

        $schoolclass->instructors()->detach();
        if(array_key_exists('instrutores', $validated)){
            foreach($validated['instrutores'] as $instructor){
                $nompes = Pessoa::obterNome($instructor['codpes']);
                $instructor['nompes'] = $nompes;
                $schoolclass->instructors()->attach(Instructor::firstOrCreate($instructor));
            }
        }

        $schoolclass->classschedules()->detach();
        if(array_key_exists('horarios', $validated)){
            foreach($validated['horarios'] as $classSchedule){
                $schoolclass->classschedules()->attach(ClassSchedule::firstOrCreate($classSchedule));
            }
        }

        $schoolclass->update($validated);

        return redirect('/schoolclasses');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SchoolClass  $schoolclass
     * @return \Illuminate\Http\Response
     */
    public function destroy(SchoolClass $schoolclass)
    {
        if(!Gate::allows('deletar turma')){
            abort(403);
        }
        $schoolclass->instructors()->detach();
        $schoolclass->classschedules()->detach();
        $schoolclass->delete();

        return redirect('/schoolclasses');
    }

    public function import(CreateSchoolClassRequest $request)
    {   
        if(!Gate::allows('importar turmas do replicado')){
            abort(403);
        }

        $validated = $request->validated();
        $schoolTerm = SchoolTerm::where(['id'=>$validated["periodoId"]])->first();
        
        if(env('IS_SUPERVISOR_CONFIG')){
            ProcessGetSchoolClassesFromReplicado::dispatch($schoolTerm);
        }else{
            foreach($turmas as $turma){
                $schoolclass = SchoolClass::where(array_intersect_key($turma, array_flip(array('codtur', 'coddis'))))->first();
    
                if(!$schoolclass){
                    $schoolclass = new SchoolClass;
                    $schoolclass->fill($turma);
                    $schoolclass->save();
            
                    foreach($turma['instructors'] as $instructor){
                        $schoolclass->instructors()->attach(Instructor::firstOrCreate($instructor));
                    }
        
                    foreach($turma['class_schedules'] as $classSchedule){
                        $schoolclass->classschedules()->attach(ClassSchedule::firstOrCreate($classSchedule));
                    }
                    $schoolclass->save();
                }
            }
        }

        return redirect("/schoolclasses");
    }

    public function search(SearchSchoolClassRequest $request)
    {
        if(!Gate::allows('visualizar inscrição')){
            abort(403);
        }

        $validated = $request->validated();
        
        $coddis = $validated['coddis'];

        $turmas = new SchoolClass;
        $turmas = $turmas->when($coddis, function ($query) use ($coddis) {
            return $query->where('coddis', $coddis);
        })->get();

        $schoolterms = SchoolTerm::all();

        return view('schoolclasses.index', compact(['turmas', 'schoolterms']));
    }

    public function enrollments(SchoolClass $schoolclass)
    {
        $turma = $schoolclass;

        return view('schoolclasses.enrollments', compact('turma'));
    }

    public function electedTutors($schoolclass){        
        $turma = SchoolClass::find($schoolclass);
        
        return view('schoolclasses.electedTutors', [
            'turma' => $turma,
        ]);
    }

    public function showFrequencies($schoolclass, $tutor){
        $monitor = Student::find($tutor);
        $turma = SchoolClass::find($schoolclass);

        return view('schoolclasses.frequencies', [
            'monitor' => $monitor,
            'turma' => $turma
        ]);
    }
}
