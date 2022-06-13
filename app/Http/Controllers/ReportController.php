<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Ismaelw\LaraTeX\LaraTeX;
use App\Models\SchoolTerm;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\MakeReportRequest;

class ReportController extends Controller
{
    public function index()
    {
        if(!Gate::allows('gerar relatorio')){
            abort(403);
        }
        $schoolterms = SchoolTerm::all();
        return view('reports.index', compact('schoolterms'));
    }

    public function make(MakeReportRequest $request)
    {
        if(!Gate::allows('gerar relatorio')){
            abort(403);
        }

        $validated = $request->validated();

        return (new LaraTeX('reports.latex'))->with([
            'schoolterm' => SchoolTerm::find($validated['periodoId']),
        ])->download('relatorio.pdf');
    }
}
