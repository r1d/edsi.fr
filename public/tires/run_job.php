<?php
/**
 * Tire Tracker — Worker de scraping en arrière-plan
 * Lancé par ajax.php via exec(), écrit le résultat dans /tmp/tt_{jobId}.json
 *
 * Usage : php run_job.php <jobId>
 */
declare(strict_types=1);
set_time_limit(0);

$jobId   = $argv[1] ?? exit(1);
$jobFile = sys_get_temp_dir() . '/tt_' . $jobId . '.json';

// Marquer comme en cours (au cas où ajax.php n'a pas eu le temps)
if (!file_exists($jobFile)) {
    file_put_contents($jobFile, json_encode(['status' => 'running']));
}

try {
    require_once __DIR__ . '/config.php';
    date_default_timezone_set(TIMEZONE);

    foreach (['Database', 'BaseScraper', 'PneudealScraper', 'Pneus971Scraper',
              'SearchEngine', 'ResultBuilder'] as $cls) {
        require_once SRC_PATH . "/{$cls}.php";
    }

    Database::init();
    $sites   = require __DIR__ . '/sites.php';
    $engine  = new SearchEngine($sites);
    $builder = new ResultBuilder($sites);

    $dims1 = Database::getDimensions(activeOnly: true);
    $dims2 = Database::getDimensionsMarques(activeOnly: true);

    $rows1  = $engine->runAll($dims1);
    $rows2  = $engine->runBranded($dims2);
    $errors = $engine->collectErrors($rows1, $rows2);

    $tz      = new DateTimeZone(TIMEZONE);
    $runDate = (new DateTime('now', $tz))->format('d/m/Y H:i:s');

    file_put_contents($jobFile, json_encode([
        'status'  => 'done',
        'table1'  => $builder->buildTable1Html($rows1),
        'table2'  => $builder->buildTable2Html($rows2),
        'errors'  => $errors,
        'runDate' => $runDate,
        'counts'  => ['t1' => count($rows1), 't2' => count($rows2)],
    ]));

} catch (Throwable $e) {
    file_put_contents($jobFile, json_encode([
        'status' => 'error',
        'error'  => $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(),
    ]));
    error_log('[TireTracker:run_job] ' . $e->getMessage());
}
