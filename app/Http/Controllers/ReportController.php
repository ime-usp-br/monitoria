<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Ismaelw\LaraTeX\LaraTeX;
use App\Models\SchoolTerm;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\MakeReportRequest;
use Symfony\Component\Process\Process;

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

        $p = new Process(["/usr/bin/python3", base_path()."/app/Scripts/Python/create_graphs.py", $validated['periodoId']]);
        $p->run();

        return (new LaraTeX('reports.latex'))->with([
            'schoolterm' => SchoolTerm::find($validated['periodoId']),
        ])->download('relatorio.pdf');
    }
}
