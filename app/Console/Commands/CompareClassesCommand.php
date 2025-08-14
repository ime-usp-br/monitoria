<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SchoolClass;
use App\Models\SchoolTerm;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Exception;

class CompareClassesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:compare-classes 
                            {--format=table : Output format (table, json, csv)}
                            {--schoolterm= : Specific school term ID (default: open term)}
                            {--output= : File path to save report}
                            {--detailed : Show detailed differences}
                            {--only-instructor-diffs : Show only instructor differences}
                            {--show-instructor-details : Show instructor codes and full names}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compare class instructors between local database and Replicado USP system';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $this->info('🔍 Iniciando comparação de turmas...');
            
            // Validation
            if (!$this->validateEnvironment()) {
                return 1;
            }

            // Get school term
            $schoolTerm = $this->getSchoolTerm();
            if (!$schoolTerm) {
                $this->error('❌ Nenhum período letivo encontrado.');
                return 1;
            }

            $this->info("📚 Analisando professores - Período: {$schoolTerm->period} de {$schoolTerm->year}");

            // Collect data
            $this->info('📊 Coletando dados locais...');
            $localClasses = $this->getLocalClasses($schoolTerm);
            
            $this->info('🌐 Coletando dados do Replicado...');
            $replicadoClasses = $this->getReplicadoClasses($schoolTerm);

            // Compare data
            $this->info('👥 Comparando professores...');
            $comparison = $this->compareClasses($localClasses, $replicadoClasses);

            // Generate report
            $this->generateReport($comparison, $schoolTerm);

            $this->info('✅ Relatório gerado com sucesso!');
            return 0;

        } catch (Exception $e) {
            $this->error("❌ Erro durante execução: {$e->getMessage()}");
            if ($this->option('detailed')) {
                $this->error($e->getTraceAsString());
            }
            return 1;
        }
    }

    /**
     * Validate environment requirements
     */
    private function validateEnvironment(): bool
    {
        // Check if Replicado package is available
        if (!class_exists('\Uspdev\Replicado\DB')) {
            $this->error('❌ Pacote Uspdev\Replicado não encontrado.');
            return false;
        }

        // Check UNIDADE environment variable
        if (!env('UNIDADE')) {
            $this->error('❌ Variável de ambiente UNIDADE não configurada.');
            return false;
        }

        // Check database connection
        try {
            \DB::connection()->getPdo();
        } catch (Exception $e) {
            $this->error('❌ Falha na conexão com o banco de dados local.');
            return false;
        }

        return true;
    }

    /**
     * Get school term for comparison
     */
    private function getSchoolTerm(): ?SchoolTerm
    {
        if ($schoolTermId = $this->option('schoolterm')) {
            return SchoolTerm::find($schoolTermId);
        }

        return SchoolTerm::getOpenSchoolTerm();
    }

    /**
     * Get local classes from database
     */
    private function getLocalClasses(SchoolTerm $schoolTerm): Collection
    {
        return SchoolClass::with(['instructors', 'department', 'classSchedules'])
            ->where('school_term_id', $schoolTerm->id)
            ->get()
            ->map(function ($class) {
                return [
                    'key' => $class->codtur . '_' . $class->coddis,
                    'codtur' => $class->codtur,
                    'coddis' => $class->coddis,
                    'nomdis' => $class->nomdis,
                    'dtainitur' => $class->dtainitur,
                    'dtafimtur' => $class->dtafimtur,
                    'tiptur' => $class->tiptur,
                    'department' => $class->department ? $class->department->nomabvset : 'N/A',
                    'instructors' => $class->instructors->map(function($instructor) {
                        return [
                            'codpes' => $instructor->codpes,
                            'nompes' => $instructor->nompes
                        ];
                    })->toArray(),
                    'instructor_names' => $class->instructors->pluck('nompes')->toArray(),
                    'instructor_codes' => $class->instructors->pluck('codpes')->toArray(),
                    'source' => 'local'
                ];
            });
    }

    /**
     * Get classes from Replicado
     */
    private function getReplicadoClasses(SchoolTerm $schoolTerm): Collection
    {
        try {
            $replicadoData = SchoolClass::getFromReplicadoBySchoolTerm($schoolTerm);
            
            return collect($replicadoData)->map(function ($class) {
                return [
                    'key' => $class['codtur'] . '_' . $class['coddis'],
                    'codtur' => $class['codtur'],
                    'coddis' => $class['coddis'],
                    'nomdis' => $class['nomdis'],
                    'dtainitur' => $class['dtainitur'],
                    'dtafimtur' => $class['dtafimtur'],
                    'tiptur' => $class['tiptur'] ?? 'N/A',
                    'department' => $class['pfxdisval'] ?? 'N/A',
                    'instructors' => collect($class['instructors'] ?? [])->map(function($instructor) {
                        return [
                            'codpes' => $instructor['codpes'] ?? '',
                            'nompes' => $instructor['nompes'] ?? ''
                        ];
                    })->toArray(),
                    'instructor_names' => collect($class['instructors'] ?? [])->pluck('nompes')->toArray(),
                    'instructor_codes' => collect($class['instructors'] ?? [])->pluck('codpes')->toArray(),
                    'source' => 'replicado'
                ];
            });

        } catch (Exception $e) {
            $this->warn("⚠️ Falha ao conectar com Replicado: {$e->getMessage()}");
            return collect();
        }
    }

    /**
     * Compare local and Replicado classes
     */
    private function compareClasses(Collection $localClasses, Collection $replicadoClasses): array
    {
        $localKeys = $localClasses->pluck('key');
        $replicadoKeys = $replicadoClasses->pluck('key');

        $onlyLocal = $localClasses->filter(fn($class) => !$replicadoKeys->contains($class['key']));
        $onlyReplicado = $replicadoClasses->filter(fn($class) => !$localKeys->contains($class['key']));
        $inBoth = $localClasses->filter(fn($class) => $replicadoKeys->contains($class['key']));

        $instructorDifferences = collect();
        $otherDifferences = collect();
        $identicalInstructors = collect();
        
        foreach ($inBoth as $localClass) {
            $replicadoClass = $replicadoClasses->firstWhere('key', $localClass['key']);
            $diffs = $this->findDifferences($localClass, $replicadoClass);
            
            // Separate instructor differences from other differences
            if (isset($diffs['instructors'])) {
                $instructorDifferences->push([
                    'key' => $localClass['key'],
                    'codtur' => $localClass['codtur'],
                    'coddis' => $localClass['coddis'],
                    'nomdis' => $localClass['nomdis'],
                    'local_instructors' => $diffs['instructors']['local'],
                    'replicado_instructors' => $diffs['instructors']['replicado'],
                    'local_instructor_details' => $localClass['instructors'],
                    'replicado_instructor_details' => $replicadoClass['instructors'],
                    'other_differences' => array_filter($diffs, fn($key) => $key !== 'instructors', ARRAY_FILTER_USE_KEY)
                ]);
            } else {
                // Same instructors
                $identicalInstructors->push([
                    'key' => $localClass['key'],
                    'codtur' => $localClass['codtur'],
                    'coddis' => $localClass['coddis'],
                    'nomdis' => $localClass['nomdis'],
                    'instructors' => $localClass['instructor_names']
                ]);
                
                // But might have other differences
                if (!empty($diffs)) {
                    $otherDifferences->push([
                        'key' => $localClass['key'],
                        'coddis' => $localClass['coddis'],
                        'nomdis' => $localClass['nomdis'],
                        'differences' => $diffs
                    ]);
                }
            }
        }

        return [
            'summary' => [
                'local_total' => $localClasses->count(),
                'replicado_total' => $replicadoClasses->count(),
                'only_local' => $onlyLocal->count(),
                'only_replicado' => $onlyReplicado->count(),
                'instructor_differences' => $instructorDifferences->count(),
                'identical_instructors' => $identicalInstructors->count(),
                'other_differences' => $otherDifferences->count(),
                'instructor_sync_rate' => $inBoth->count() > 0 ? 
                    round(($identicalInstructors->count() / $inBoth->count()) * 100, 1) : 0,
            ],
            'only_local' => $onlyLocal,
            'only_replicado' => $onlyReplicado,
            'instructor_differences' => $instructorDifferences,
            'identical_instructors' => $identicalInstructors,
            'other_differences' => $otherDifferences
        ];
    }

    /**
     * Find differences between local and Replicado class
     */
    private function findDifferences(array $local, array $replicado): array
    {
        $differences = [];

        // Compare basic fields
        $fieldsToCompare = ['nomdis', 'dtainitur', 'dtafimtur', 'tiptur', 'department'];
        
        foreach ($fieldsToCompare as $field) {
            if ($local[$field] !== $replicado[$field]) {
                $differences[$field] = [
                    'local' => $local[$field],
                    'replicado' => $replicado[$field]
                ];
            }
        }

        // Compare instructors by codes (more reliable than names)
        $localInstructorCodes = array_values(array_unique($local['instructor_codes']));
        $replicadoInstructorCodes = array_values(array_unique($replicado['instructor_codes']));
        sort($localInstructorCodes);
        sort($replicadoInstructorCodes);

        if ($localInstructorCodes !== $replicadoInstructorCodes) {
            $differences['instructors'] = [
                'local' => $local['instructor_names'],
                'replicado' => $replicado['instructor_names'],
                'local_codes' => $localInstructorCodes,
                'replicado_codes' => $replicadoInstructorCodes
            ];
        }

        return $differences;
    }

    /**
     * Generate and display report
     */
    private function generateReport(array $comparison, SchoolTerm $schoolTerm): void
    {
        $format = $this->option('format');
        $outputFile = $this->option('output');

        switch ($format) {
            case 'json':
                $output = $this->generateJsonReport($comparison, $schoolTerm);
                break;
            case 'csv':
                $output = $this->generateCsvReport($comparison, $schoolTerm);
                break;
            default:
                $this->displayTableReport($comparison, $schoolTerm);
                return;
        }

        if ($outputFile) {
            file_put_contents($outputFile, $output);
            $this->info("💾 Relatório salvo em: {$outputFile}");
        } else {
            $this->line($output);
        }
    }

    /**
     * Display table format report
     */
    private function displayTableReport(array $comparison, SchoolTerm $schoolTerm): void
    {
        $this->newLine();
        $this->info("👥 RELATÓRIO DE COMPARAÇÃO DE PROFESSORES - {$schoolTerm->period} {$schoolTerm->year}");
        $this->info(str_repeat('=', 70));

        // Summary
        $summary = $comparison['summary'];
        $this->table(
            ['Métrica', 'Quantidade', '%'],
            [
                ['Total Turmas Local', $summary['local_total'], ''],
                ['Total Turmas Replicado', $summary['replicado_total'], ''],
                ['Turmas Apenas Local', $summary['only_local'], ''],
                ['Turmas Apenas Replicado', $summary['only_replicado'], ''],
                ['🎯 Professores Idênticos', $summary['identical_instructors'], $summary['instructor_sync_rate'] . '%'],
                ['⚠️ Professores Diferentes', $summary['instructor_differences'], ''],
                ['📄 Outras Diferenças', $summary['other_differences'], ''],
            ]
        );

        // Only in local
        if ($comparison['only_local']->isNotEmpty()) {
            $this->newLine();
            $this->warn('⚠️ TURMAS APENAS NO SISTEMA LOCAL:');
            $this->table(
                ['Código Turma', 'Código Disciplina', 'Nome Disciplina'],
                $comparison['only_local']->map(fn($class) => [
                    $class['codtur'],
                    $class['coddis'],
                    $class['nomdis']
                ])->toArray()
            );
        }

        // Only in Replicado
        if ($comparison['only_replicado']->isNotEmpty()) {
            $this->newLine();
            $this->warn('⚠️ TURMAS APENAS NO REPLICADO:');
            $this->table(
                ['Código Turma', 'Código Disciplina', 'Nome Disciplina'],
                $comparison['only_replicado']->map(fn($class) => [
                    $class['codtur'],
                    $class['coddis'],
                    $class['nomdis']
                ])->toArray()
            );
        }

        // Instructor differences (main focus)
        if ($comparison['instructor_differences']->isNotEmpty()) {
            $this->newLine();
            $this->error('🚨 TURMAS COM PROFESSORES DIFERENTES:');
            
            if ($this->option('only-instructor-diffs') || !$this->option('detailed')) {
                // Simplified table for instructor differences
                $this->table(
                    ['Turma', 'Disciplina', 'Professores Local', 'Professores Replicado'],
                    $comparison['instructor_differences']->map(function($diff) {
                        return [
                            $diff['codtur'],
                            $diff['coddis'] . ' - ' . substr($diff['nomdis'], 0, 30),
                            implode(', ', $diff['local_instructors']),
                            implode(', ', $diff['replicado_instructors'])
                        ];
                    })->toArray()
                );
            }
            
            if ($this->option('detailed') || $this->option('show-instructor-details')) {
                foreach ($comparison['instructor_differences'] as $diff) {
                    $this->warn("▶ {$diff['codtur']} - {$diff['coddis']} - {$diff['nomdis']}");
                    
                    $this->line("  📍 Local:");
                    foreach ($diff['local_instructor_details'] as $instructor) {
                        $this->line("    - {$instructor['nompes']} ({$instructor['codpes']})");
                    }
                    
                    $this->line("  🌐 Replicado:");
                    foreach ($diff['replicado_instructor_details'] as $instructor) {
                        $this->line("    - {$instructor['nompes']} ({$instructor['codpes']})");
                    }
                    
                    if (!empty($diff['other_differences'])) {
                        $this->line("  📄 Outras diferenças:");
                        foreach ($diff['other_differences'] as $field => $values) {
                            $local = is_array($values['local']) ? implode(', ', $values['local']) : $values['local'];
                            $replicado = is_array($values['replicado']) ? implode(', ', $values['replicado']) : $values['replicado'];
                            $this->line("    {$field}: Local[{$local}] ≠ Replicado[{$replicado}]");
                        }
                    }
                    $this->line('');
                }
            }
        }

        // Show synchronized instructors if detailed
        if ($comparison['identical_instructors']->isNotEmpty() && $this->option('detailed')) {
            $this->newLine();
            $this->info('✅ TURMAS COM PROFESSORES SINCRONIZADOS:');
            $this->table(
                ['Turma', 'Disciplina', 'Professores'],
                $comparison['identical_instructors']->map(function($class) {
                    return [
                        $class['codtur'],
                        $class['coddis'],
                        implode(', ', $class['instructors'])
                    ];
                })->toArray()
            );
        }

        // Other differences (non-instructor)
        if ($comparison['other_differences']->isNotEmpty() && $this->option('detailed') && !$this->option('only-instructor-diffs')) {
            $this->newLine();
            $this->warn('📄 OUTRAS DIFERENÇAS (não relacionadas a professores):');
            
            foreach ($comparison['other_differences'] as $diff) {
                $this->warn("▶ {$diff['coddis']} - {$diff['nomdis']}");
                foreach ($diff['differences'] as $field => $values) {
                    $local = is_array($values['local']) ? implode(', ', $values['local']) : $values['local'];
                    $replicado = is_array($values['replicado']) ? implode(', ', $values['replicado']) : $values['replicado'];
                    $this->line("  {$field}: Local[{$local}] ≠ Replicado[{$replicado}]");
                }
                $this->line('');
            }
        }
    }

    /**
     * Generate JSON report
     */
    private function generateJsonReport(array $comparison, SchoolTerm $schoolTerm): string
    {
        $report = [
            'metadata' => [
                'school_term' => "{$schoolTerm->period} {$schoolTerm->year}",
                'generated_at' => now()->toISOString(),
                'command' => 'report:compare-classes',
                'focus' => 'instructor_comparison'
            ],
            'summary' => $comparison['summary'],
            'instructor_analysis' => [
                'instructor_differences' => $comparison['instructor_differences']->values(),
                'identical_instructors' => $comparison['identical_instructors']->values(),
            ],
            'class_availability' => [
                'only_local' => $comparison['only_local']->values(),
                'only_replicado' => $comparison['only_replicado']->values(),
            ],
            'other_differences' => $comparison['other_differences']->values()
        ];

        return json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Generate CSV report
     */
    private function generateCsvReport(array $comparison, SchoolTerm $schoolTerm): string
    {
        $csv = [];
        $csv[] = "Relatório de Comparação de Professores - {$schoolTerm->period} {$schoolTerm->year}";
        $csv[] = "Gerado em: " . now()->format('d/m/Y H:i:s');
        $csv[] = "";
        
        // Summary
        $csv[] = "RESUMO";
        $csv[] = "Métrica,Quantidade,Percentual";
        foreach ($comparison['summary'] as $key => $value) {
            $percent = $key === 'instructor_sync_rate' ? $value . '%' : '';
            $csv[] = "{$key},{$value},{$percent}";
        }
        $csv[] = "";

        // Instructor differences
        if ($comparison['instructor_differences']->isNotEmpty()) {
            $csv[] = "TURMAS COM PROFESSORES DIFERENTES";
            $csv[] = "Código Turma,Código Disciplina,Nome Disciplina,Professores Local,Professores Replicado";
            foreach ($comparison['instructor_differences'] as $diff) {
                $localInstructors = '"' . implode('; ', $diff['local_instructors']) . '"';
                $replicadoInstructors = '"' . implode('; ', $diff['replicado_instructors']) . '"';
                $csv[] = "{$diff['codtur']},{$diff['coddis']},\"{$diff['nomdis']}\",{$localInstructors},{$replicadoInstructors}";
            }
            $csv[] = "";
        }

        // Identical instructors
        if ($comparison['identical_instructors']->isNotEmpty()) {
            $csv[] = "TURMAS COM PROFESSORES SINCRONIZADOS";
            $csv[] = "Código Turma,Código Disciplina,Professores";
            foreach ($comparison['identical_instructors'] as $class) {
                $instructors = '"' . implode('; ', $class['instructors']) . '"';
                $csv[] = "{$class['codtur']},{$class['coddis']},{$instructors}";
            }
            $csv[] = "";
        }

        // Classes availability
        if ($comparison['only_local']->isNotEmpty()) {
            $csv[] = "TURMAS APENAS NO LOCAL";
            $csv[] = "Código Turma,Código Disciplina,Nome Disciplina";
            foreach ($comparison['only_local'] as $class) {
                $csv[] = "{$class['codtur']},{$class['coddis']},\"{$class['nomdis']}\"";
            }
            $csv[] = "";
        }

        if ($comparison['only_replicado']->isNotEmpty()) {
            $csv[] = "TURMAS APENAS NO REPLICADO";
            $csv[] = "Código Turma,Código Disciplina,Nome Disciplina";
            foreach ($comparison['only_replicado'] as $class) {
                $csv[] = "{$class['codtur']},{$class['coddis']},\"{$class['nomdis']}\"";
            }
        }

        return implode("\n", $csv);
    }
}