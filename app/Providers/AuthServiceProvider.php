<?php

namespace App\Providers;

use App\Models\Enrollment;
use App\Models\InstructorEvaluation;
use App\Models\Requisition;
use App\Models\SchoolClass;
use App\Models\SchoolRecord;
use App\Models\SelfEvaluation;
use App\Policies\EnrollmentPolicy;
use App\Policies\InstructorEvaluationPolicy;
use App\Policies\RequisitionPolicy;
use App\Policies\SchoolClassPolicy;
use App\Policies\SchoolRecordPolicy;
use App\Policies\SelfEvaluationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Enrollment::class => EnrollmentPolicy::class,
        InstructorEvaluation::class => InstructorEvaluationPolicy::class,
        Requisition::class => RequisitionPolicy::class,
        SchoolClass::class => SchoolClassPolicy::class,
        SchoolRecord::class => SchoolRecordPolicy::class,
        SelfEvaluation::class => SelfEvaluationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
