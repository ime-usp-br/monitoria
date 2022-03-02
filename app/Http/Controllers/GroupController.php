<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGroupRequest;
use App\Http\Requests\UpdateGroupRequest;
use App\Http\Requests\CreateGroupRequest;
use App\Http\Requests\SearchGroupRequest;
use App\Models\Group;
use App\Models\Instructor;
use App\Models\ClassSchedule;
use App\Models\SchoolTerm;
use App\Models\Department;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Uspdev\Replicado\Pessoa;
use Session;
use Auth;

class GroupController extends Controller
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
            $turmas = Group::whereHas('instructors', function($query) { 
                $query->where('instructors.codpes', Auth::user()->codpes); 
            })->get();
        }else{
            $turmas = Group::all();
        }

        $schoolterms = SchoolTerm::all();

        return view('groups.index', compact(['turmas', 'schoolterms']));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(CreateGroupRequest $request)
    {
        if(!Gate::allows('criar turma')){
            abort(403);
        }

        $turma = new Group;
        $validated = $request->validated();
        $schoolTerm = SchoolTerm::find($validated["periodoId"]);
        $turma->schoolterm()->associate($schoolTerm);

        foreach(Department::getFromReplicadoByInstitute(env("UNIDADE")) as $department){
            Department::firstOrCreate($department);
        }

        return view('groups.create', compact('turma'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreGroupRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreGroupRequest $request)
    {
        if(!Gate::allows('criar turma')){
            abort(403);
        }

        $validated = $request->validated();
        $schoolTerm = SchoolTerm::find($validated["periodoId"]);
        $group = Group::where(array_intersect_key($validated, array_flip(array('codtur', 'coddis'))))->first();

        if(!$group){
            $group = new Group;

            $group->fill($validated);

            $group->schoolterm()->associate($schoolTerm);
            $group->save();

            if(array_key_exists('instrutores', $validated)){
                foreach($validated['instrutores'] as $instructor){
                    $group->instructors()->attach(Instructor::firstOrCreate(Instructor::getFromReplicadoByCodpes($instructor['codpes'])));
                }
            }   

            if(array_key_exists('horarios', $validated)){
                foreach($validated['horarios'] as $classSchedule){
                    $group->classschedules()->attach(ClassSchedule::firstOrCreate($classSchedule));
                }
            }
            $group->save();
        }else{
            Session::flash("alert-warning", "Já existe uma turma cadastrada com esse código da turma e código da disciplina");
            return back();
        }

        return redirect('/groups');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function show(Group $group)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function edit(Group $group)
    {
        if(!Gate::allows('editar turma')){
            abort(403);
        }

        $turma = $group;

        return view('groups.edit', compact('turma'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateGroupRequest  $request
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateGroupRequest $request, Group $group)
    {
        if(!Gate::allows('editar turma')){
            abort(403);
        }

        $validated = $request->validated();

        $group->instructors()->detach();
        if(array_key_exists('instrutores', $validated)){
            foreach($validated['instrutores'] as $instructor){
                $nompes = Pessoa::obterNome($instructor['codpes']);
                $instructor['nompes'] = $nompes;
                $group->instructors()->attach(Instructor::firstOrCreate($instructor));
            }
        }

        $group->classschedules()->detach();
        if(array_key_exists('horarios', $validated)){
            foreach($validated['horarios'] as $classSchedule){
                $group->classschedules()->attach(ClassSchedule::firstOrCreate($classSchedule));
            }
        }

        $group->update($validated);

        return redirect('/groups');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Group  $groupperiodoId
     * @return \Illuminate\Http\Response
     */
    public function destroy(Group $group)
    {
        if(!Gate::allows('deletar turma')){
            abort(403);
        }
        $group->instructors()->detach();
        $group->classschedules()->detach();
        $group->delete();

        return redirect('/groups');
    }

    public function import(CreateGroupRequest $request)
    {   
        if(!Gate::allows('importar turmas do replicado')){
            abort(403);
        }

        $validated = $request->validated();
        $schoolTerm = SchoolTerm::find($validated["periodoId"]);
        $turmas = Group::getFromReplicadoBySchoolTerm($schoolTerm);
        foreach($turmas as $turma){
            $group = Group::where(array_intersect_key($turma, array_flip(array('codtur', 'coddis'))))->first();

            if(!$group){
                $group = new Group;
                $group->fill($turma);
                $group->save();
        
                foreach($turma['instructors'] as $instructor){
                    $group->instructors()->attach(Instructor::firstOrCreate($instructor));
                }
    
                foreach($turma['class_schedules'] as $classSchedule){
                    $group->classschedules()->attach(ClassSchedule::firstOrCreate($classSchedule));
                }
                $group->save();
            }
        }

        return redirect("/groups");
    }

    public function search(SearchGroupRequest $request){
        $validated = $request->validated();
        
        $coddis = $validated['coddis'];

        $turmas = new Group;
        $turmas = $turmas->when($coddis, function ($query) use ($coddis) {
            return $query->where('coddis', $coddis);
        })->get();

        $schoolterms = SchoolTerm::all();

        return view('groups.index', compact(['turmas', 'schoolterms']));
    }
}
