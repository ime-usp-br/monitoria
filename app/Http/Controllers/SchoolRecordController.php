<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSchoolRecordRequest;
use App\Http\Requests\UpdateSchoolRecordRequest;
use App\Http\Requests\DownloadSchoolRecordRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use App\Models\SchoolRecord;
use App\Models\SchoolTerm;
use App\Models\Student;
use Auth;

class SchoolRecordController extends Controller
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
        if(!Auth::user()->hasRole('Aluno')){
            abort(403);
        }elseif(!SchoolTerm::isEnrollmentPeriod()){
            Session::flash('alert-warning', 'Período de inscrições encerrado');
            return redirect('/');
        }

        return view('schoolrecords.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreSchoolRecordRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreSchoolRecordRequest $request)
    {
        if(!Auth::user()->hasRole('Aluno')){
            abort(403);
        }elseif(!SchoolTerm::isEnrollmentPeriod()){
            Session::flash('alert-warning', 'Período de inscrições encerrado');
            return redirect('/');
        }

        $validated = $request->validated();

        $schoolterm = SchoolTerm::getSchoolTermInEnrollmentPeriod();

        $path = $validated['file']->store($schoolterm->year . $schoolterm->period[0]);

        $historico = new SchoolRecord;

        $historico->file_path = $path;

        $historico->schoolterm()->associate($schoolterm);
        $historico->student()->associate(Student::where(['codpes'=>Auth::user()->codpes])->first());

        $historico->save();
        
        return redirect(route('enrollments.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\SchoolRecord  $schoolRecord
     * @return \Illuminate\Http\Response
     */
    public function show(SchoolRecord $schoolRecord)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SchoolRecord  $schoolRecord
     * @return \Illuminate\Http\Response
     */
    public function edit(SchoolRecord $schoolRecord)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateSchoolRecordRequest  $request
     * @param  \App\Models\SchoolRecord  $schoolRecord
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateSchoolRecordRequest $request, SchoolRecord $schoolRecord)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SchoolRecord  $schoolRecord
     * @return \Illuminate\Http\Response
     */
    public function destroy(SchoolRecord $schoolRecord)
    {
        //
    }

    public function download(DownloadSchoolRecordRequest $request)
    {
        if(!Gate::allows('baixar histórico escolar')){
            abort(403);
        }

        $validated = $request->validated();

        return Storage::download($validated['path'], explode('/',$validated['path'])[0] . '.pdf');
    }
}
