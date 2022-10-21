<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ImportOldDBRequest;
use App\Models\Student;
use App\Models\Instructor;
use App\Models\SchoolTerm;
use App\Models\SchoolClass;
use App\Models\Frequency;
use App\Models\Requisition;
use App\Models\Enrollment;
use App\Models\Selection;
use App\Models\ClassSchedule;
use App\Models\Activity;
use App\Models\SelfEvaluation;
use App\Models\InstructorEvaluation;
use App\Jobs\ProcessImportOldDB;
use Session;
use Auth;

class OldDBController extends Controller
{

    public function index()
    {
        if(Auth::check()){
            if(!Auth::user()->hasRole("Administrador")){
                abort(403);   
            }
        }else{
            return redirect("login");
        }
        return view("olddb.index");
    }

    public function import(ImportOldDBRequest $request)
    {
        if(Auth::check()){
            if(!Auth::user()->hasRole("Administrador")){
                abort(403);   
            }
        }else{
            return redirect("login");
        }
        
        $validated = $request->validated();

        ProcessImportOldDB::dispatch($validated["file"]->get(), Auth::user()->codpes);
        return back();
    }
}
