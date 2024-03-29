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
use App\Http\Requests\IndexSchoolClassRequest;
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
    public function index(IndexSchoolClassRequest $request)
    {
        if(!Gate::allows('visualizar turma')){
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
        
        if(Auth::user()->hasRole('Docente') && !Auth::user()->hasRole("Membro Comissão")){
            $turmas = $schoolterm ? SchoolClass::whereHas('instructors', function($query) { 
                return $query->where('instructors.codpes', Auth::user()->codpes); 
            })->whereBelongsTo($schoolterm)->get() : [];
        }elseif(Auth::user()->hasRole("Membro Comissão") && !Auth::user()->hasRole("Secretaria")){
            $turmas = $schoolterm ? SchoolClass::whereBelongsTo($schoolterm)
                ->whereBelongsTo(Instructor::where(['codpes'=>Auth::user()->codpes])->first()->department)->get() : [];
        }else{
            $turmas = $schoolterm ? SchoolClass::whereBelongsTo($schoolterm)->get() : [];
        }

        return view('schoolclasses.index', compact(['turmas', 'schoolterm']));
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

            foreach($validated['instrutores'] as $instructor){
                $schoolclass->instructors()->attach(Instructor::firstOrCreate(Instructor::getFromReplicadoByCodpes($instructor['codpes'])));
            }

            if(array_key_exists('horarios', $validated)){
                foreach($validated['horarios'] as $classSchedule){
                    $schoolclass->classschedules()->attach(ClassSchedule::firstOrCreate($classSchedule));
                }
            }
            $schoolclass->save();
        }elseif(!$schoolclass->instructors()->exists()){
            foreach($validated['instrutores'] as $instructor){
                $schoolclass->instructors()->attach(Instructor::firstOrCreate(Instructor::getFromReplicadoByCodpes($instructor['codpes'])));
            }
    
            $schoolclass->classschedules()->detach();
            if(array_key_exists('horarios', $validated)){
                foreach($validated['horarios'] as $classSchedule){
                    $schoolclass->classschedules()->attach(ClassSchedule::firstOrCreate($classSchedule));
                }
            }
    
            $schoolclass->update($validated);            
        }else{
            Session::flash("alert-warning", "Já existe uma turma cadastrada com esse código de turma e código de disciplina");
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
        foreach($validated['instrutores'] as $instructor){
            $nompes = Pessoa::obterNome($instructor['codpes']);
            $instructor['nompes'] = $nompes;
            $schoolclass->instructors()->attach(Instructor::firstOrCreate($instructor));
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
        $schoolTerm = SchoolTerm::find($validated["periodoId"]);
        
        if(env('IS_SUPERVISOR_CONFIG')){
            ProcessGetSchoolClassesFromReplicado::dispatch($schoolTerm);
        }else{
            $turmas = SchoolClass::getFromReplicadoBySchoolTerm($schoolTerm);

            foreach($turmas as $turma){
                $schoolclass = SchoolClass::where(array_intersect_key($turma, array_flip(array('codtur', 'coddis'))))->first();
    
                if(!$schoolclass){
                    $schoolclass = new SchoolClass;
                    $schoolclass->fill($turma);
                    $schoolclass->save();
            
                    foreach($turma['instructors'] as $instructor){
                        $schoolclass->instructors()->attach(Instructor::firstOrCreate(Instructor::getFromReplicadoByCodpes($instructor["codpes"])));
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
        if(!Gate::allows('visualizar turma')){
            abort(403);
        }

        $validated = $request->validated();

        $schoolterm = array_key_exists('periodoId', $validated) ? SchoolTerm::find($validated['periodoId']) : [];

        $turmas = $schoolterm ? SchoolClass::whereBelongsTo($schoolterm)
                         ->where('coddis', $validated['coddis'])->get() : [];

        return view('schoolclasses.index', compact(['turmas', 'schoolterm']));
    }

    public function enrollments(SchoolClass $schoolclass)
    {
        if(!Gate::allows('visualizar inscrição')){
            abort(403);
        }

        $turma = $schoolclass;

        return view('schoolclasses.enrollments', compact('turma'));
    }

    public function electedTutors($schoolclass)
    {   
        if(!Gate::allows('registrar frequencia')){
            abort(403);
        }

        $turma = SchoolClass::find($schoolclass);

        return view('schoolclasses.electedTutors', [
            'turma' => $turma,
        ]);
        
    }
}
