<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    \Illuminate\Support\Facades\DB::statement('DROP TABLE IF EXISTS `equipo_historial_refaccions`');
    echo "Query fired.\n";
    $tables = \Illuminate\Support\Facades\DB::select('SHOW TABLES LIKE "%equipo_historial%"');
    print_r($tables);
    \Illuminate\Support\Facades\DB::table('migrations')->where('migration', 'like', '%equipo_historial_refaccions%')->delete();
} catch (\Exception $e) {
    echo $e->getMessage();
}
