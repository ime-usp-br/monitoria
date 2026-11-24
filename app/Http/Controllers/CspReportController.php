<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CspReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        $report = is_array($payload) ? ($payload['csp-report'] ?? $payload) : [];

        Log::warning('csp_report', [
            'report' => $report,
            'ip' => $request->ip(),
        ]);

        return response()->noContent();
    }
}
