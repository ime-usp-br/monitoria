<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSelectionRequest;
use App\Http\Requests\UpdateSelectionRequest;
use App\Http\Requests\SelectUnenrolledSelectionRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\Selection;
use App\Models\Instructor;
use App\Models\Requisition;
use App\Models\SchoolTerm;
use App\Models\SchoolClass;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Frequency;
use App\Models\Course;
use Session;

class SelectionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(!Gate::allows('Selecionar monitor')){
            abort(403);
        }

        $schoolterm = SchoolTerm::getOpenSchoolTerm();

        if(!$schoolterm){
            Session::flash('alert-warning', 'Não foi encontrado um periodo letivo com status em aberto.');
            return back();
        }

        if(Auth::user()->hasRole(['Secretaria', 'Administrador', 'Presidente de Comissão'])){
            $solicitacoes = Requisition::whereHas('schoolclass', function($q) use($schoolterm){
                return $q->whereBelongsTo($schoolterm);})->get()->sortBy('schoolclass.department.nomabvset');
        }elseif(Auth::user()->hasRole('Membro Comissão')){
            $docente = Instructor::where(['codpes'=>Auth::user()->codpes])->first();

            $departamento = $docente->department;

            $solicitacoes = Requisition::whereHas('schoolclass', function($q) use($departamento, $schoolterm){
                return $q->whereBelongsTo($departamento)->whereBelongsTo($schoolterm);})->get();
        }

        return view('selections.index', compact('solicitacoes'));
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
     * @param  \App\Http\Requests\StoreSelectionRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreSelectionRequest $request)
    {
        if(!Gate::allows('Selecionar monitor')){
            abort(403);
        }

        $validated = $request->validated();

        $inscricao = Enrollment::find($validated['enrollment_id']);

        if($inscricao->selection){
            Frequency::whereBelongsTo($inscricao->selection->student)->whereBelongsTo($inscricao->selection->schoolclass)->delete();
            $inscricao->selection->selfevaluation()->delete();
            $inscricao->selection->instructorevaluation()->delete();
            $inscricao->selection->delete();
        }

        $validated['student_id'] = $inscricao->student->id;
        $validated['school_class_id'] = $inscricao->schoolclass->id;
        $validated['requisition_id'] = $inscricao->schoolclass->requisition->id;
        $validated['codpescad'] = Auth::user()->codpes;
        $validated['sitatl'] = "Ativo";

        $course = Course::getCourseFromReplicado($inscricao->student, $inscricao->schoolclass->schoolterm);

        if($course){
            Course::updateOrCreate(["student_id"=>$inscricao->student->id,"schoolterm_id"=>$inscricao->schoolclass->schoolterm->id],$course);
        }

        $schoolterm = SchoolTerm::getOpenSchoolTerm();

        if(!$schoolterm){
            Session::flash('alert-warning', 'Não foi encontrado um periodo letivo com status em aberto.');
            return back();
        }

        if($inscricao->student->selections()
                              ->whereHas('schoolclass.schoolterm', function ($query) {return $query->where(['status'=>'Aberto']);})
                              ->where('school_class_id', '!=', $inscricao->schoolclass->id)->exists()){
            Session::flash('alert-warning', 'Aluno já foi eleito monitor de outra turma.');
            return back();
        }
        
        if(Auth::user()->hasRole(['Secretaria', 'Administrador','Presidente de Comissão'])){
            $selecao = Selection::firstOrCreate($validated);
            Frequency::createFromSelection($selecao);
        }elseif(Auth::user()->hasRole('Membro Comissão')){
            $docente = Instructor::where(['codpes'=>Auth::user()->codpes])->first();
            if($inscricao->schoolclass->department == $docente->department){
                $selecao = Selection::firstOrCreate($validated);
                Frequency::createFromSelection($selecao);
            }else{
                abort(403);
            }
        }else{
            abort(403);
        }

        return back();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Selection  $selection
     * @return \Illuminate\Http\Response
     */
    public function show(Selection $selection)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Selection  $selection
     * @return \Illuminate\Http\Response
     */
    public function edit(Selection $selection)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateSelectionRequest  $request
     * @param  \App\Models\Selection  $selection
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateSelectionRequest $request, Selection $selection)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Selection  $selection
     * @return \Illuminate\Http\Response
     */
    public function destroy(Selection $selection)
    {
        if(!Gate::allows('Preterir monitor')){
            abort(403);
        }

        $hasPresence = Frequency::whereBelongsTo($selection->student)->whereBelongsTo($selection->schoolclass)->where("registered", true)->exists();

        if($hasPresence){
            Session::flash('alert-warning', 'Não é possível preterir o aluno, pois existe registro de presença na monitoria. Efetue o desligamento do aluno no menu Monitores.');
            return back();
        }

        if(Auth::user()->hasRole(['Secretaria', 'Administrador','Presidente de Comissão'])){
            Frequency::whereBelongsTo($selection->student)->whereBelongsTo($selection->schoolclass)->delete();
            $selection->delete();
        }elseif(Auth::user()->hasRole('Membro Comissão')){
            $docente = Instructor::where(['codpes'=>Auth::user()->codpes])->first();
            if($selection->schoolclass->department == $docente->department){
                Frequency::whereBelongsTo($selection->student)->whereBelongsTo($selection->schoolclass)->delete();
                $selection->delete();
            }else{
                abort(403);
            }
        }else{
            abort(403);
        }
        
        return back();
    }

    public function enrollments(SchoolClass $schoolclass)
    {
        if(!Gate::allows('Selecionar monitor')){
            abort(403);
        }

        $turma = $schoolclass;

        $inscricoes = $schoolclass->enrollments;

        $inscricoes = $inscricoes->sort(function($a,$b){
            if($a->selection and !$b->selection){
                return 1;
            }elseif(!$a->selection and $b->selection){
                return -1;
            }

            if($a->student->hasSelectionInOpenSchoolTerm() and !$b->student->hasSelectionInOpenSchoolTerm()){
                return -1;
            }elseif(!$a->student->hasSelectionInOpenSchoolTerm() and $b->student->hasSelectionInOpenSchoolTerm()){
                return 1;
            }

            if($a->schoolclass->requisition->recommendations){
                $aIsRecommended = $a->schoolclass->requisition->recommendations()->whereHas("student",function($query)use($a){
                    $query->where("codpes",$a->student->codpes);
                })->first();
                $bIsRecommended = $b->schoolclass->requisition->recommendations()->whereHas("student",function($query)use($b){
                    $query->where("codpes",$b->student->codpes);
                })->first();

                if($aIsRecommended and !$bIsRecommended){
                    return 1;
                }elseif(!$aIsRecommended and $bIsRecommended){
                    return -1;
                }
            }
        })->reverse();

        return view('selections.enrollments', compact(['turma', 'inscricoes']));
    }

    public function selectUnenrolled(SelectUnenrolledSelectionRequest $request){
        $validated = $request->validated();
        
        $estudante = Student::firstOrCreate(Student::getFromReplicadoByCodpes($validated['codpes']));
        $validated['student_id'] = $estudante->id;
        unset($validated['codpes']);

        if(Enrollment::where(['school_class_id'=>$validated['school_class_id'],
                             'student_id'=>$validated['student_id']])->first()){
            Session::flash('alert-warning', 'O aluno já está inscrito nesta turma, caso ele não 
                esteja na lista de inscritos, entrar em contato com a Secretaria de Monitoria');
            return back();
        }elseif($estudante->hasSelectionInOpenSchoolTerm()){
            Session::flash('alert-warning', 'Este aluno já foi eleito monitor da turma ' . 
                $estudante->getSelectionFromOpenSchoolTerm()->schoolclass->codtur . ' da disciplina ' .
                $estudante->getSelectionFromOpenSchoolTerm()->schoolclass->coddis);
            return back();
        }elseif(!$estudante->getSchoolRecordFromOpenSchoolTerm()){
            Session::flash('alert-warning', 'Este aluno não subiu o histórico escolar no período letivo virgente');
            return back();
        }

        $validated['codpescad'] = Auth::user()->codpes;

        $inscricao = Enrollment::create([
            'student_id'=>$validated['student_id'],
            'school_class_id' => $validated['school_class_id'],
            'disponibilidade_diurno' => 0,
            'disponibilidade_noturno' => 0,
            'voluntario' => 0,
            'observacoes' => 'Eleição feita sem inscrição por ' . Auth::user()->name,
            'preferencia_horario' => 'Indiferente',
        ]);

        $validated['enrollment_id'] = $inscricao->id;
        
        $validated['sitatl'] = "Ativo";

        $validated['requisition_id'] = SchoolClass::where(['id'=>$validated['school_class_id']])->first()->requisition->id;

        if(Auth::user()->hasRole('Membro Comissão')){
            $docente = Instructor::where(['codpes'=>Auth::user()->codpes])->first();
            if($inscricao->schoolclass->department == $docente->department){
                $selecao = Selection::firstOrCreate($validated);
            }else{
                abort(403);
            }
        }elseif(Auth::user()->hasRole(['Secretaria', 'Administrador'])){
            $selecao = Selection::firstOrCreate($validated);
        }else{
            abort(403);
        }

        return back();
    }
}
