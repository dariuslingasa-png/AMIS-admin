<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$tables = DB::select('SHOW TABLES');
$dbName = env('DB_DATABASE', 'amisdavc_amis');
$keyName = 'Tables_in_'.$dbName;

echo "--- Searching for student references in database ---\n";
foreach ($tables as $t) {
    $tableName = $t->$keyName;

    // Check if table contains search term
    // Let's search columns in this table
    $columns = Schema::getColumnListing($tableName);

    // Build query to search in all string/numeric columns
    $query = DB::table($tableName);
    $first = true;

    foreach ($columns as $col) {
        if ($first) {
            $query->where($col, 'like', '%260124%')
                ->orWhere($col, 'like', '%AMIR SHAHEEN%');
            $first = false;
        } else {
            $query->orWhere($col, 'like', '%260124%')
                ->orWhere($col, 'like', '%AMIR%SHAHEEN%');
        }
    }

    if (! $first) {
        try {
            $results = $query->limit(5)->get();
            if ($results->count() > 0) {
                echo "Table [{$tableName}] matched:\n";
                foreach ($results as $row) {
                    echo '  Row: '.json_encode($row)."\n";
                }
            }
        } catch (Exception $e) {
            // Ignore syntax/execution errors on incompatible columns
        }
    }
}
