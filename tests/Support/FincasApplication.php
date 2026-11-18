<?php

namespace Tests\Support;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Support\Env;

final class FincasApplication
{
    public static function create(string $database = ':memory:', bool $browser = false): Application
    {
        $engine = $browser ? 'sqlite' : (getenv('BOVISOFT_TEST_ENGINE') ?: 'sqlite');
        if (! in_array($engine, ['sqlite', 'mysql', 'pgsql'], true)) {
            throw new \RuntimeException('Motor de pruebas no permitido.');
        }
        if ($engine !== 'sqlite') {
            $database = getenv('BOVISOFT_TEST_DATABASE') ?: '';
            if (! preg_match('/^bovisoft_test_piloto_[a-f0-9]{16}$/', $database)) {
                throw new \RuntimeException('Solo se permiten bases desechables creadas por tests/run-pilot.php.');
            }
        }

        // Never load local credentials or cached deployment configuration.
        foreach (['APP_CONFIG_CACHE', 'APP_ROUTES_CACHE'] as $name) {
            Env::getRepository()->set($name, sys_get_temp_dir().'/bovisoft-fincas-'.getmypid().'-'.$name.'.php');
        }

        Env::getRepository()->set('APP_ENV', $browser ? 'local' : 'testing');

        $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $app->loadEnvironmentFrom('.env.example');
        $app->afterBootstrapping(LoadConfiguration::class, function ($app) use ($database, $browser, $engine) {
            $connection = $app['config']->get('database.connections.'.$engine);
            $connection['url'] = null;
            $connection['database'] = $database;
            if ($engine === 'sqlite') {
                $connection['foreign_key_constraints'] = true;
            } else {
                // Explicit local test settings; never inherit DB_* credentials.
                $connection['host'] = '127.0.0.1';
                $connection['port'] = $engine === 'mysql' ? 3306 : 5432;
                $connection['username'] = $engine === 'mysql' ? 'root' : 'postgres';
                $connection['password'] = '';
                $connection['unix_socket'] = '';
                $connection['options'] = [];
            }

            $app['config']->set([
                'app.env' => $browser ? 'local' : 'testing',
                'app.debug' => false,
                'app.key' => 'base64:'.base64_encode(str_repeat('t', 32)),
                'database.default' => $engine,
                'database.connections' => [$engine => $connection],
                'cache.default' => 'array',
                'session.driver' => $browser ? 'database' : 'array',
                'session.cookie' => 'bovisoft_fincas_demo',
                'session.domain' => null,
                'session.secure' => false,
                'queue.default' => 'sync',
                'mail.default' => 'array',
                'logging.default' => 'null',
                'hashing.bcrypt.rounds' => 4,
            ]);
        });

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
