<?php

namespace App\Http\Controllers;

use App\Http\Requests\MakeReportRequest;
use App\Models\SchoolTerm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Ismaelw\LaraTeX\LaraTeX;
use Symfony\Component\Process\Process;

class ReportController extends Controller
{
    public function index()
    {
        if (!Gate::allows('gerar relatorio')) {
            abort(403);
        }

        $schoolterms = SchoolTerm::all();

        return view('reports.index', compact('schoolterms'));
    }

    public function make(MakeReportRequest $request)
    {
        if (!Gate::allows('gerar relatorio')) {
            abort(403);
        }

        $validated = $request->validated();

        try {
            $this->generateGraphs((int) $validated['periodoId']);
        } catch (\Throwable $exception) {
            Log::error('Falha ao gerar gráficos do relatório.', [
                'periodo' => $validated['periodoId'],
                'exception' => $exception->getMessage(),
            ]);

            abort(500, 'Não foi possível gerar os gráficos do relatório.');
        }

        return (new LaraTeX('reports.latex'))->with([
            'schoolterm' => SchoolTerm::find($validated['periodoId']),
        ])->download('relatorio.pdf');
    }

    public function external(Request $request)
    {
        $token = $request->bearerToken() ?: $request->input('token');

        if (empty($token)) {
            return response()->json([
                'status' => false,
                'message' => 'Não foi encontrado o token.',
            ]);
        }

        $expectedToken = (string) config('services.external_report.token');

        if (!hash_equals($expectedToken, (string) $token)) {
            return response()->json([
                'status' => false,
                'message' => 'Token incorreto.',
            ]);
        }

        if (!$request->has('ano')) {
            return response()->json([
                'status' => false,
                'message' => 'Não foi encontrado o ano.',
            ]);
        }

        if (!$request->has('periodo')) {
            return response()->json([
                'status' => false,
                'message' => 'Não foi encontrado o periodo.',
            ]);
        }

        $semestre = SchoolTerm::where('year', $request->get('ano'))
            ->where('period', 'like', $request->get('periodo').'%')
            ->first();

        if (!$semestre) {
            return response()->json([
                'status' => false,
                'message' => 'Não foi encontrado um semestre que atenda sua busca.',
            ]);
        }

        try {
            $this->generateGraphs((int) $semestre->id);
        } catch (\Throwable $exception) {
            Log::error('Falha ao gerar gráficos do relatório externo.', [
                'periodo' => $semestre->id,
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Não foi possível gerar os gráficos do relatório.',
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Relatório gerado com sucesso.',
            'report' => (new LaraTeX('reports.latex-external'))->with([
                'schoolterm' => $semestre,
            ])->content('base64'),
        ]);
    }

    private function generateGraphs(int $schoolTermId): void
    {
        $process = new Process([
            config('services.report.python_cmd'),
            base_path().'/app/Scripts/Python/create_graphs.py',
            (string) $schoolTermId,
        ]);

        $process->setTimeout(120);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput()));
        }
    }
}
