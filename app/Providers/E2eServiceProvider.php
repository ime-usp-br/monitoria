<?php

namespace App\Providers;

use App\Support\E2e\FakeReplicadoDb;
use Illuminate\Support\ServiceProvider;

class E2eServiceProvider extends ServiceProvider
{
    /**
     * Em ambientes em que o ASE do asedocker não roda (por exemplo kernels
     * muito novos), o Replicado pode ser substituído por fixtures em JSON.
     * O alias precisa acontecer antes de qualquer uso de Uspdev\Replicado\DB.
     */
    public function register()
    {
        if (! filter_var(env('E2E_MODE', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        if (! $this->app->environment('testing')) {
            throw new \RuntimeException('E2E_MODE só é permitido com APP_ENV=testing.');
        }

        if (! filter_var(env('E2E_REPLICADO_FAKE', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        if (! class_exists('Uspdev\\Replicado\\DB', false)) {
            class_alias(FakeReplicadoDb::class, 'Uspdev\\Replicado\\DB');
        }
    }
}
