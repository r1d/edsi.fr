<?php
/**
 * Tire Tracker — Point d'entrée AJAX
 * Toutes les actions de l'interface web passent ici.
 */
declare(strict_types=1);

ob_start(); // capture tout output parasite avant le JSON

require_once __DIR__ . '/config.php';
require_once SRC_PATH . '/Database.php';

session_name(SESSION_NAME);
session_start();

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

// ── Authentification ───────────────────────────────────────────────────────
if (empty($_SESSION['authenticated'])) {
    http_response_code(401);
    exit(json_encode(['ok' => false, 'error' => 'Session expirée, veuillez vous reconnecter.']));
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── Dispatch ───────────────────────────────────────────────────────────────
try {
    Database::init();

    switch ($action) {

        // ════════════════════════════════════════════════════════════════════
        // Tableau 1 — Dimensions (toutes marques)
        // ════════════════════════════════════════════════════════════════════

        case 'list_dims':
            echo json_encode(['ok' => true, 'data' => Database::getDimensions()]);
            break;

        case 'add_dim':
            $raw = trim($_POST['dim'] ?? '');
            [$largeur, $hauteur, $diametre] = parseDim($raw); // throws on error
            $ok = Database::addDimension($largeur, $hauteur, $diametre);
            echo json_encode([
                'ok'    => $ok,
                'error' => $ok ? null : "La dimension {$raw} existe déjà.",
            ]);
            break;

        case 'toggle_dim':
            Database::toggleDimension((int) ($_POST['id'] ?? 0));
            echo json_encode(['ok' => true]);
            break;

        case 'delete_dim':
            Database::deleteDimension((int) ($_POST['id'] ?? 0));
            echo json_encode(['ok' => true]);
            break;

        // ════════════════════════════════════════════════════════════════════
        // Tableau 2 — Dimensions + Marques
        // ════════════════════════════════════════════════════════════════════

        case 'list_dm':
            echo json_encode(['ok' => true, 'data' => Database::getDimensionsMarques()]);
            break;

        case 'add_dm':
            $raw    = trim($_POST['dim']    ?? '');
            $marque = trim($_POST['marque'] ?? '');
            [$largeur, $hauteur, $diametre] = parseDim($raw);
            if (strlen($marque) < 2) {
                throw new InvalidArgumentException('Marque invalide (minimum 2 caractères).');
            }
            $ok = Database::addDimensionMarque($largeur, $hauteur, $diametre, $marque);
            echo json_encode([
                'ok'    => $ok,
                'error' => $ok ? null : "La combinaison {$marque} {$raw} existe déjà.",
            ]);
            break;

        case 'toggle_dm':
            Database::toggleDimensionMarque((int) ($_POST['id'] ?? 0));
            echo json_encode(['ok' => true]);
            break;

        case 'delete_dm':
            Database::deleteDimensionMarque((int) ($_POST['id'] ?? 0));
            echo json_encode(['ok' => true]);
            break;

        // ════════════════════════════════════════════════════════════════════
        // Lancement de la recherche (background job — évite le timeout nginx)
        // ════════════════════════════════════════════════════════════════════

        case 'run':
            $jobId   = 'job_' . bin2hex(random_bytes(8));
            $jobFile = sys_get_temp_dir() . '/tt_' . $jobId . '.json';

            // Écrire le statut initial avant de lancer le process
            file_put_contents($jobFile, json_encode(['status' => 'running']));

            $phpBin  = escapeshellarg(PHP_BINARY);
            $script  = escapeshellarg(__DIR__ . '/run_job.php');
            $arg     = escapeshellarg($jobId);
            $logFile = sys_get_temp_dir() . '/tt_' . $jobId . '_exec.log';
            $cmd     = "{$phpBin} {$script} {$arg} > " . escapeshellarg($logFile) . " 2>&1 &";

            exec($cmd);

            // Laisser 1s au process pour écrire son fichier job
            sleep(1);
            $launched = file_exists($jobFile)
                ? json_decode(file_get_contents($jobFile), true)
                : null;

            echo json_encode([
                'ok'     => true,
                'jobId'  => $jobId,
                'debug'  => [
                    'php'      => PHP_BINARY,
                    'tmpDir'   => sys_get_temp_dir(),
                    'jobFile'  => $jobFile,
                    'fileOk'   => file_exists($jobFile),
                    'status'   => $launched['status'] ?? 'no-file',
                    'execLog'  => file_exists($logFile) ? file_get_contents($logFile) : '(empty)',
                ],
            ]);
            break;

        case 'poll':
            $jobId = trim($_POST['jobId'] ?? '');
            if (!preg_match('/^job_[a-f0-9]+$/', $jobId)) {
                throw new InvalidArgumentException('Job ID invalide');
            }
            $jobFile = sys_get_temp_dir() . '/tt_' . $jobId . '.json';

            if (!file_exists($jobFile)) {
                echo json_encode(['ok' => true, 'status' => 'unknown']);
                break;
            }

            $data = json_decode(file_get_contents($jobFile), true);
            echo json_encode(array_merge(['ok' => true], $data ?? ['status' => 'error']));

            // Nettoyage du fichier temporaire une fois lu
            if (in_array($data['status'] ?? '', ['done', 'error'])) {
                @unlink($jobFile);
            }
            break;

        default:
            throw new InvalidArgumentException("Action inconnue : {$action}");
    }

} catch (InvalidArgumentException $e) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    ob_end_clean();
    error_log('[TireTracker:ajax] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

// ── Helper ─────────────────────────────────────────────────────────────────

/**
 * Valide et parse "205/55/16" → [205, 55, 16]
 * @throws InvalidArgumentException
 */
function parseDim(string $raw): array
{
    $parts = array_map('trim', explode('/', $raw));
    if (count($parts) !== 3
        || !ctype_digit($parts[0]) || !ctype_digit($parts[1]) || !ctype_digit($parts[2])
        || (int) $parts[0] < 100 || (int) $parts[0] > 400
        || (int) $parts[1] <  25 || (int) $parts[1] > 100
        || (int) $parts[2] <  10 || (int) $parts[2] >  30
    ) {
        throw new InvalidArgumentException(
            "Format de dimension invalide : « {$raw} ». Attendu : largeur/hauteur/diametre (ex: 205/55/16)"
        );
    }
    return [(int) $parts[0], (int) $parts[1], (int) $parts[2]];
}
