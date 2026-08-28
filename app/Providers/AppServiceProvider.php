<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {       
        // Garante que o log do uspdev/replicado aponte para um local gravável
        // e SEMPRE absoluto. Em deploy sem Docker o CWD do worker varia
        // (supervisor inicia de /), então um caminho relativo como
        // storage/logs/replicado.log cairia em /storage/... e voltaria a dar
        // "Permission denied". Se REPLICADO_PATHLOG vier do .env, é respeitado,
        // mas convertido para absoluto via base_path().
        $replicadoLog = getenv('REPLICADO_PATHLOG');
        if (empty($replicadoLog)) {
            $replicadoLog = storage_path('logs/replicado.log');
        } elseif (!$this->isAbsolutePath($replicadoLog)) {
            $replicadoLog = base_path($replicadoLog);
        }
        if (!is_dir(dirname($replicadoLog))) {
            @mkdir(dirname($replicadoLog), 0775, true);
        }
        if (!file_exists($replicadoLog)) {
            @touch($replicadoLog);
            @chmod($replicadoLog, 0664);
        }
        putenv('REPLICADO_PATHLOG=' . $replicadoLog);

        if (env('APP_ENV') === 'production') {
            $this->app['request']->server->set('HTTPS', true);
        }
    }

    protected function isAbsolutePath(string $path): bool
    {
        return $path[0] === '/' || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
	Schema::defaultStringLength(191);
	
	if ($this->app->environment('local') || $this->app->environment('development')) {
            Mail::alwaysTo(env('MAIL_DEV_TEST'));
        }

        if (env('FORCE_HTTPS', false)){
                URL::forceScheme('https');
        }

    }
}
