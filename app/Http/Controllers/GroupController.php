<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGroupRequest;
use App\Http\Requests\UpdateGroupRequest;
use App\Http\Requests\CreateGroupRequest;
use App\Models\Group;
use App\Models\instructor;
use App\Models\ClassSchedule;
use App\Models\SchoolTerm;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Uspdev\Replicado\Pessoa;
use Session;

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
        }

        $turmas = Group::all();
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
                    $nompes = Pessoa::obterNome($instructor['codpes']);
                    $instructor['nompes'] = $nompes;
                    $group->instructors()->attach(Instructor::firstOrCreate($instructor));
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
     * @param  \App\Models\Group  $group
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
        if(!Gate::allows('criar turma')){
            abort(403);
        }

        $validated = $request->validated();
        $schoolTerm = SchoolTerm::find($validated["periodoId"]);
        $turmas = Group::getGroupsFromReplicado($schoolTerm);
        foreach($turmas as $turma){
            $group = Group::where(array_intersect_key($turma, array_flip(array('codtur', 'coddis'))))->first();

            if(!$group){
                $group = new Group;
                $group->fill($turma);

                $group->schoolterm()->associate($schoolTerm);
                $group->save();
    
                foreach($turma['instructor'] as $instructor){
                    $group->instructors()->attach(Instructor::firstOrCreate($instructor));
                }
    
                foreach($turma['class_schedule'] as $classSchedule){
                    $group->classschedules()->attach(ClassSchedule::firstOrCreate($classSchedule));
                }
                $group->save();

            }
        }

        return redirect("/groups");
    }
}
