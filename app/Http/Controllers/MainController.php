<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Instructor;
use Auth;


class MainController extends Controller
{
    public function index()
    {
        if(Auth::user()){
            if(Auth::user()->hasRole('Aluno') && !Student::where(['codpes'=>Auth::user()->codpes])->exists()){
                Student::create(Student::getFromReplicadoByCodpes(Auth::user()->codpes));
            }
            if(Auth::user()->hasRole('Docente') && !Instructor::where(['codpes'=>Auth::user()->codpes])->exists()){
                Instructor::create(Instructor::getFromReplicadoByCodpes(Auth::user()->codpes));
            }
        }

        return view('parent');
    }
}
