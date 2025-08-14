<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SchoolClass;
use App\Models\SchoolTerm;
use App\Models\Instructor;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Exception;
use DB;

class SyncClassInstructorsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:class-instructors 
                            {--schoolterm= : Specific school term ID (default: open term)}
                            {--dry-run : Return JSON preview without applying changes}
                            {--class= : Sync specific class ID only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync class instructors between local database and Replicado (additive only)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            // Validation
            if (!$this->validateEnvironment()) {
                return $this->jsonError('Environment validation failed', 1);
            }

            // Get school term
            $schoolTerm = $this->getSchoolTerm();
            if (!$schoolTerm) {
                return $this->jsonError('No school term found', 1);
            }

            // Collect data
            $localClasses = $this->collectLocalData($schoolTerm);
            $replicadoClasses = $this->collectReplicadoData($schoolTerm);

            // Find missing instructors
            $syncPlan = $this->findMissingInstructors($localClasses, $replicadoClasses);

            if ($this->option('dry-run')) {
                // Return JSON preview for frontend
                $this->outputJson($this->generateDryRunResponse($syncPlan, $schoolTerm));
                return 0;
            } else {
                // Apply changes and return execution summary
                $results = $this->syncInstructors($syncPlan, $schoolTerm);
                $this->outputJson($this->generateProductionResponse($results, $schoolTerm));
                return 0;
            }

        } catch (Exception $e) {
            return $this->jsonError($e->getMessage(), 1);
        }
    }

    /**
     * Validate environment requirements
     */
    private function validateEnvironment(): bool
    {
        // Check if Replicado package is available
        if (!class_exists('\\Uspdev\\Replicado\\DB')) {
            return false;
        }

        // Check UNIDADE environment variable
        if (!env('UNIDADE')) {
            return false;
        }

        // Check database connection
        try {
            DB::connection()->getPdo();
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Get school term for synchronization
     */
    private function getSchoolTerm(): ?SchoolTerm
    {
        if ($schoolTermId = $this->option('schoolterm')) {
            return SchoolTerm::find($schoolTermId);
        }

        return SchoolTerm::getOpenSchoolTerm();
    }

    /**
     * Collect local classes and their instructors
     */
    private function collectLocalData(SchoolTerm $schoolTerm): Collection
    {
        $query = SchoolClass::with(['instructors', 'department'])
            ->where('school_term_id', $schoolTerm->id);

        // Filter by specific class if requested
        if ($classId = $this->option('class')) {
            $query->where('id', $classId);
        }

        return $query->get()->map(function ($class) {
            return [
                'id' => $class->id,
                'codtur' => $class->codtur,
                'coddis' => $class->coddis,
                'nomdis' => $class->nomdis,
                'key' => $class->codtur . '_' . $class->coddis,
                'instructors' => $class->instructors->map(function($instructor) {
                    return [
                        'id' => $instructor->id,
                        'codpes' => $instructor->codpes,
                        'nompes' => $instructor->nompes,
                        'codema' => $instructor->codema
                    ];
                })->toArray(),
                'instructor_codes' => $class->instructors->pluck('codpes')->toArray()
            ];
        });
    }

    /**
     * Collect Replicado classes and their instructors
     */
    private function collectReplicadoData(SchoolTerm $schoolTerm): Collection
    {
        try {
            $replicadoData = SchoolClass::getFromReplicadoBySchoolTerm($schoolTerm);
            
            return collect($replicadoData)->map(function ($class) {
                return [
                    'codtur' => $class['codtur'],
                    'coddis' => $class['coddis'],
                    'nomdis' => $class['nomdis'],
                    'key' => $class['codtur'] . '_' . $class['coddis'],
                    'instructors' => collect($class['instructors'] ?? [])->map(function($instructor) {
                        return [
                            'codpes' => $instructor['codpes'] ?? '',
                            'nompes' => $instructor['nompes'] ?? '',
                            'codema' => $instructor['codema'] ?? '',
                            'department_id' => $instructor['department_id'] ?? null
                        ];
                    })->toArray(),
                    'instructor_codes' => collect($class['instructors'] ?? [])->pluck('codpes')->toArray()
                ];
            });

        } catch (Exception $e) {
            // Return empty collection if Replicado fails
            return collect();
        }
    }

    /**
     * Find instructors that need to be added (additive only)
     */
    private function findMissingInstructors(Collection $localClasses, Collection $replicadoClasses): array
    {
        $syncPlan = [
            'classes_to_sync' => [],
            'total_instructors_to_add' => 0,
            'classes_analyzed' => $localClasses->count(),
            'errors' => []
        ];

        foreach ($localClasses as $localClass) {
            $replicadoClass = $replicadoClasses->firstWhere('key', $localClass['key']);
            
            if (!$replicadoClass) {
                // Class exists locally but not in Replicado - skip
                continue;
            }

            // Find instructors in Replicado but not in local (additive only)
            $localCodes = array_values(array_unique($localClass['instructor_codes']));
            $replicadoCodes = array_values(array_unique($replicadoClass['instructor_codes']));
            $missingCodes = array_diff($replicadoCodes, $localCodes);

            if (!empty($missingCodes)) {
                $instructorsToAdd = collect($replicadoClass['instructors'])
                    ->whereIn('codpes', $missingCodes)
                    ->values()
                    ->toArray();

                $syncPlan['classes_to_sync'][] = [
                    'local_class' => $localClass,
                    'replicado_class' => $replicadoClass,
                    'instructors_to_add' => $instructorsToAdd,
                    'missing_codes' => $missingCodes
                ];

                $syncPlan['total_instructors_to_add'] += count($instructorsToAdd);
            }
        }

        return $syncPlan;
    }

    /**
     * Apply instructor synchronization changes
     */
    private function syncInstructors(array $syncPlan, SchoolTerm $schoolTerm): array
    {
        $results = [
            'classes_processed' => 0,
            'instructors_created' => 0,
            'relationships_created' => 0,
            'errors' => [],
            'execution_start' => now(),
            'class_results' => []
        ];

        DB::beginTransaction();

        try {
            foreach ($syncPlan['classes_to_sync'] as $classSync) {
                $localClass = SchoolClass::find($classSync['local_class']['id']);
                $instructorsAdded = 0;
                $classErrors = [];

                foreach ($classSync['instructors_to_add'] as $instructorData) {
                    try {
                        // Get or create instructor from Replicado data
                        $instructor = $this->getOrCreateInstructor($instructorData);
                        
                        if ($instructor) {
                            // Add relationship if it doesn't exist
                            if (!$localClass->instructors()->where('instructor_id', $instructor->id)->exists()) {
                                $localClass->instructors()->attach($instructor->id);
                                $instructorsAdded++;
                                $results['relationships_created']++;
                            }
                        }
                    } catch (Exception $e) {
                        $classErrors[] = [
                            'instructor_codpes' => $instructorData['codpes'],
                            'error' => $e->getMessage()
                        ];
                    }
                }

                $results['class_results'][] = [
                    'class_id' => $localClass->id,
                    'codtur' => $localClass->codtur,
                    'coddis' => $localClass->coddis,
                    'instructors_added' => $instructorsAdded,
                    'errors' => $classErrors,
                    'success' => empty($classErrors)
                ];

                $results['classes_processed']++;
            }

            DB::commit();
            $results['execution_time'] = now()->diffInSeconds($results['execution_start']) . 's';

        } catch (Exception $e) {
            DB::rollback();
            $results['errors'][] = 'Transaction failed: ' . $e->getMessage();
        }

        return $results;
    }

    /**
     * Get or create instructor from Replicado data
     */
    private function getOrCreateInstructor(array $instructorData): ?Instructor
    {
        // Try to find existing instructor by codpes
        $instructor = Instructor::where('codpes', $instructorData['codpes'])->first();
        
        if ($instructor) {
            return $instructor;
        }

        // Get fresh data from Replicado and create instructor
        try {
            $replicadoInstructorData = Instructor::getFromReplicadoByCodpes($instructorData['codpes']);
            
            if (empty($replicadoInstructorData)) {
                return null;
            }

            // Create instructor with validated Replicado data
            $instructor = Instructor::create([
                'codpes' => $replicadoInstructorData['codpes'],
                'nompes' => $replicadoInstructorData['nompes'],
                'codema' => $replicadoInstructorData['codema'] ?? null,
                'department_id' => $replicadoInstructorData['department_id'] ?? null
            ]);

            return $instructor;

        } catch (Exception $e) {
            throw new Exception("Failed to create instructor {$instructorData['codpes']}: " . $e->getMessage());
        }
    }

    /**
     * Generate dry-run JSON response for frontend
     */
    private function generateDryRunResponse(array $syncPlan, SchoolTerm $schoolTerm): array
    {
        $changes = [];

        foreach ($syncPlan['classes_to_sync'] as $classSync) {
            $changes[] = [
                'class_id' => $classSync['local_class']['id'],
                'class_code' => $classSync['local_class']['codtur'],
                'class_name' => $classSync['local_class']['nomdis'],
                'current_instructors' => $classSync['local_class']['instructors'],
                'instructors_to_add' => $classSync['instructors_to_add']
            ];
        }

        return [
            'status' => 'preview',
            'school_term' => [
                'id' => $schoolTerm->id,
                'period' => $schoolTerm->period,
                'year' => $schoolTerm->year
            ],
            'summary' => [
                'classes_analyzed' => $syncPlan['classes_analyzed'],
                'instructors_to_add' => $syncPlan['total_instructors_to_add'],
                'classes_affected' => count($syncPlan['classes_to_sync'])
            ],
            'changes' => $changes,
            'errors' => $syncPlan['errors']
        ];
    }

    /**
     * Generate production execution JSON response for frontend
     */
    private function generateProductionResponse(array $results, SchoolTerm $schoolTerm): array
    {
        return [
            'status' => 'completed',
            'school_term' => [
                'id' => $schoolTerm->id,
                'period' => $schoolTerm->period,
                'year' => $schoolTerm->year
            ],
            'summary' => [
                'classes_processed' => $results['classes_processed'],
                'instructors_created' => $results['instructors_created'],
                'relationships_created' => $results['relationships_created'],
                'execution_time' => $results['execution_time'] ?? 'N/A'
            ],
            'results' => $results['class_results'],
            'errors' => $results['errors']
        ];
    }

    /**
     * Output JSON response
     */
    private function outputJson(array $data): void
    {
        $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Output JSON error response
     */
    private function jsonError(string $message, int $exitCode): int
    {
        $this->outputJson([
            'status' => 'error',
            'message' => $message,
            'exit_code' => $exitCode
        ]);
        
        return $exitCode;
    }
}