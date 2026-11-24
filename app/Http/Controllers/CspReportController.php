<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CspReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $raw = $request->getContent();

        if (strlen($raw) > 16384) {
            return response()->noContent();
        }

        $payload = json_decode($raw, true);
        $report = is_array($payload) ? ($payload['csp-report'] ?? $payload) : [];

        if (!is_array($report)) {
            $report = [];
        }

        Log::warning('csp_report', [
            'report' => $this->sanitize($report),
            'ip' => $request->ip(),
        ]);

        return response()->noContent();
    }

    private function sanitize(array $data): array
    {
        $allowed = array_slice($data, 0, 20, true);

        foreach ($allowed as $key => $value) {
            if (is_array($value)) {
                $allowed[$key] = $this->sanitize($value);
                continue;
            }

            if (is_string($value) && in_array($key, ['document-uri', 'blocked-uri', 'source-file', 'referrer'], true)) {
                $parts = parse_url($value);

                if (is_array($parts)) {
                    unset($parts['query'], $parts['fragment']);
                    $allowed[$key] = $this->buildUrl($parts);
                }
            }
        }

        return $allowed;
    }

    private function buildUrl(array $parts): string
    {
        $url = '';

        if (isset($parts['scheme'])) {
            $url .= $parts['scheme'].'://';
        }

        if (isset($parts['user'])) {
            $url .= $parts['user'];
            if (isset($parts['pass'])) {
                $url .= ':'.$parts['pass'];
            }
            $url .= '@';
        }

        $url .= $parts['host'] ?? '';

        if (isset($parts['port'])) {
            $url .= ':'.$parts['port'];
        }

        $url .= $parts['path'] ?? '';

        return $url;
    }
}
