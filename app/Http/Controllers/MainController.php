<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use Auth;


class MainController extends Controller
{
    public function index()
    {
        if(Auth::user()){
            if(Auth::user()->hasRole('Aluno sem cadastro')){
                return redirect(route('students.create'));
            }
        }
        return view('parent');
    }
}
