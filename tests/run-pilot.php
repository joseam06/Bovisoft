<?php

// Runs only the pilot suites against a new disposable local database.
// No .env, application bootstrap, or deployment configuration is loaded here.
$engine = $argv[1] ?? 'sqlite';
if (! in_array($engine, ['sqlite', 'mysql', 'pgsql'], true)) {
    fwrite(STDERR, "Uso: php tests/run-pilot.php sqlite|mysql|pgsql\n");
    exit(1);
}

if (! in_array($engine, PDO::getAvailableDrivers(), true)) {
    if ($engine === 'pgsql' && ! getenv('BOVISOFT_PGSQL_EXTENSION_ATTEMPTED')) {
        putenv('BOVISOFT_PGSQL_EXTENSION_ATTEMPTED=1');
        $process = proc_open([PHP_BINARY, '-d', 'extension=pdo_pgsql', __FILE__, $engine], [STDIN, STDOUT, STDERR], $pipes, dirname(__DIR__));
        exit(is_resource($process) ? proc_close($process) : 2);
    }
    fwrite(STDERR, "No está disponible el controlador PDO de $engine.\n");
    exit(2);
}

$database = 'bovisoft_test_piloto_'.bin2hex(random_bytes(8));
$pdo = null;
$created = false;
$status = 1;
$quotedName = $engine === 'mysql' ? '`'.$database.'`' : '"'.$database.'"';

try {
    if ($engine !== 'sqlite') {
        $port = $engine === 'mysql' ? 3306 : 55432;
        $username = $engine === 'mysql' ? 'root' : 'postgres';
        $dsn = "$engine:host=127.0.0.1;port=$port".($engine === 'pgsql' ? ';dbname=postgres' : '');
        try {
            // Same empty local test password as the documented example, never real credentials.
            $pdo = new PDO($dsn, $username, '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]);
        } catch (PDOException $exception) {
            fwrite(STDERR, "No hay acceso a $engine local con la configuración ficticia de pruebas; no se consultaron credenciales reales.\n");
            exit(2);
        }

        echo 'Motor: '.$engine.' '.$pdo->query('SELECT VERSION()')->fetchColumn()."\n";
        // $database is generated here, never taken from application configuration.
        $pdo->exec('CREATE DATABASE '.$quotedName);
        $created = true;
        echo "Base desechable: $database\n";
    } else {
        $sqlite = new PDO('sqlite::memory:');
        echo 'Motor: SQLite '.$sqlite->query('SELECT sqlite_version()')->fetchColumn()." en memoria\n";
    }

    putenv('BOVISOFT_TEST_ENGINE='.$engine);
    putenv('BOVISOFT_TEST_DATABASE='.$database);
    $command = [PHP_BINARY];
    if ($engine === 'pgsql' && getenv('BOVISOFT_PGSQL_EXTENSION_ATTEMPTED')) {
        array_push($command, '-d', 'extension=pdo_pgsql');
    }
    $process = proc_open(array_merge($command, [dirname(__DIR__).'/vendor/bin/phpunit',
        '--configuration', dirname(__DIR__).'/phpunit.xml',
        '--filter', 'ProduccionTest|FincaAuthorizationTest|ProductionSync', '--no-progress',
    ]), [STDIN, STDOUT, STDERR], $pipes, dirname(__DIR__));
    if (! is_resource($process)) {
        throw new RuntimeException('No se pudo iniciar PHPUnit.');
    }
    $status = proc_close($process);
} finally {
    if ($created && $pdo && preg_match('/^bovisoft_test_piloto_[a-f0-9]{16}$/', $database)) {
        $pdo->exec('DROP DATABASE '.$quotedName);
        echo "Base desechable eliminada: $database\n";
    }
}

exit($status);
