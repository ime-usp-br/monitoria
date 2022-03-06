<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInstructorRequest;
use App\Http\Requests\UpdateInstructorRequest;
use App\Http\Requests\IndexInstructorRequest;
use App\Http\Requests\SearchInstructorRequest;
use App\Models\Instructor;
use Uspdev\Replicado\Pessoa;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class InstructorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(IndexInstructorRequest $request)
    {
        $validated = $request->validated();
        
        if($request->expectsJson()){
            $codpes = $request->get('codpes');
            $nompes = Pessoa::obterNome($codpes);

            $vinculos = Pessoa::vinculos($codpes);
            if($vinculos){
                foreach($vinculos as $vinculo){
                    if(str_contains($vinculo, "Docente")){
                        return response()->json($nompes);
                    }
                }
            }

            return response()->json("");
        }

        if(!Gate::allows('visualizar docente')){
            abort(403);
        }

        $docentes = Instructor::select(DB::raw('instructors.*, SUM(teaching_assistant_applications.requested_number) as requested_number'))
        ->join('teaching_assistant_applications', 'teaching_assistant_applications.instructor_id', '=', 'instructors.id')
        ->groupBy('instructors.id')->orderBy('requested_number', 'desc')
        ->get()->merge(Instructor::doesntHave('teachingAssistantApplications')->get());


        return view('instructors.index', compact('docentes'));
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
     * @param  \App\Http\Requests\StoreInstructorRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreInstructorRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Instructor  $instructor
     * @return \Illuminate\Http\Response
     */
    public function show(Instructor $instructor)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Instructor  $instructor
     * @return \Illuminate\Http\Response
     */
    public function edit(Instructor $instructor)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateInstructorRequest  $request
     * @param  \App\Models\Instructor  $instructor
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateInstructorRequest $request, Instructor $instructor)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Instructor  $instructor
     * @return \Illuminate\Http\Response
     */
    public function destroy(Instructor $instructor)
    {
        //
    }

    public function requests(Instructor $instructor)
    {
        if(!Gate::allows('visualizar docente')){
            abort(403);
        }

        $docente = $instructor;

        return view('instructors.requests', compact('docente'));
    }

    public function search(SearchInstructorRequest $request)
    {
        if(!Gate::allows('visualizar docente')){
            abort(403);
        }
        
        $validated = $request->validated();
        $codpes = $validated['codpes'];

        $docentes = new Instructor;
        $docentes = $docentes->when($codpes, function ($query) use ($codpes){
            return $query->where('codpes', $codpes);
        })->get();

        return view('instructors.index', compact('docentes'));
    }
}
