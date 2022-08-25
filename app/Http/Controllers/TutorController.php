<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\SchoolTerm;
use App\Models\Selection;
use App\Http\Requests\IndexTutorRequest;


class TutorController extends Controller
{
    public function index(IndexTutorRequest $request)
    {
        $validated = $request->validated();

        if(isset($validated['periodoId'])){
            $schoolterm = SchoolTerm::find($validated['periodoId']);
        }else{
            $schoolterm = SchoolTerm::getOpenSchoolTerm();
        }

        if(!$schoolterm){
            Session::flash('alert-warning', 'Não foi encontrado um periodo letivo.');
            return back();
        }

        $selections = $schoolterm ? Selection::whereHas('schoolclass.schoolterm', function($query) use($schoolterm) {return $query->where('id', $schoolterm->id);})->get() : [];

        return view('tutors.index', compact(['selections', 'schoolterm']));
    }
}
