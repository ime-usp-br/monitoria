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

        $p = new Process([env("PYTHON_CMD", "/usr/bin/python3"), base_path()."/app/Scripts/Python/create_graphs.py", $validated['periodoId']]);
        $p->run();

        return (new LaraTeX('reports.latex'))->with([
            'schoolterm' => SchoolTerm::find($validated['periodoId']),
        ])->download('relatorio.pdf');
    }

    public function external(Request $request)
    {
        if($request->has("token")){
            if(env("EXTERNAL_REPORT_TOKEN")!=$request->get("token")){
                return response()->json([
                    "status"=>false,
                    "message"=>"Token incorreto."
                ]);
            }
        }else{
            return response()->json([
                "status"=>false,
                "message"=>"Não foi encontrado o token."
            ]);
        }

        if(!$request->has("ano")){
            return response()->json([
                "status"=>false,
                "message"=>"Não foi encontrado o ano."
            ]);
        }

        if(!$request->has("periodo")){
            return response()->json([
                "status"=>false,
                "message"=>"Não foi encontrado o periodo."
            ]);
        }

        $semestre = SchoolTerm::where("year", $request->get("ano"))->where("period", "like", $request->get("periodo")."%")->first();

        if(!$semestre){
            return response()->json([
                "status"=>false,
                "message"=>"Não foi encontrado um semestre que atenda sua busca."
            ]);
        }

        $p = new Process([env("PYTHON_CMD", "/usr/bin/python3"), base_path()."/app/Scripts/Python/create_graphs.py", $semestre->id]);
        $p->run();

        return response()->json([
            "status"=>false,
            "message"=>"Relatório gerado com sucesso.",
            "report"=> (new LaraTeX('reports.latex-external'))->with([
                'schoolterm' => $semestre,
            ])->content('base64')
        ]);
    }
}
