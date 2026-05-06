<?php
/**
 * Tire Tracker — Script CLI pour le cron quotidien
 *
 * Usage   : php /chemin/vers/tires/run.php
 * Cron    : 0 9 * * * php /var/www/html/tires/run.php >> /var/www/html/tires/cron.log 2>&1
 *           (9h UTC = 5h Guadeloupe)
 *
 * Ce script :
 *   1. Scrape les deux tableaux
 *   2. Envoie un email d'alerte si des erreurs sont détectées
 *   3. Envoie l'email de résultats (avec — pour les données manquantes)
 */
declare(strict_types=1);

// ── Guard : CLI uniquement ─────────────────────────────────────────────────
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Ce script doit être lancé en ligne de commande.\n");
}

set_time_limit(0);
ini_set('display_errors', '0');
ini_set('log_errors',     '1');

// ── Bootstrap ──────────────────────────────────────────────────────────────
$base = __DIR__;
require_once $base . '/config.php';
date_default_timezone_set(TIMEZONE);

foreach (['Database', 'BaseScraper', 'PneudealScraper', 'Pneus971Scraper',
          'SearchEngine', 'ResultBuilder', 'Mailer'] as $cls) {
    require_once SRC_PATH . "/{$cls}.php";
}

// ── Démarrage ──────────────────────────────────────────────────────────────
$start = microtime(true);
log_msg("═══════════════════════════════════════════");
log_msg("Tire Tracker — démarrage");

Database::init();

$sites   = require $base . '/sites.php';
$engine  = new SearchEngine($sites);
$builder = new ResultBuilder($sites);
$mailer  = new Mailer(BREVO_API_KEY);

// ── Tableau 1 ───────────────────────────────────────────────────────────────
$dims1 = Database::getDimensions(activeOnly: true);
log_msg("Tableau 1 : " . count($dims1) . " dimension(s) active(s)");

if (empty($dims1)) {
    log_msg("  → aucune dimension, tableau 1 ignoré");
    $rows1 = [];
} else {
    $rows1 = $engine->runAll($dims1);
    log_msg("  → scraping terminé");
}

// ── Tableau 2 ───────────────────────────────────────────────────────────────
$dims2 = Database::getDimensionsMarques(activeOnly: true);
log_msg("Tableau 2 : " . count($dims2) . " combinaison(s) active(s)");

if (empty($dims2)) {
    log_msg("  → aucune combinaison, tableau 2 ignoré");
    $rows2 = [];
} else {
    $rows2 = $engine->runBranded($dims2);
    log_msg("  → scraping terminé");
}

// ── Alertes erreurs ────────────────────────────────────────────────────────
$errors = $engine->collectErrors($rows1, $rows2);

if (!empty($errors)) {
    $errCount = count($errors);
    log_msg("⚠  {$errCount} erreur(s) détectée(s) — envoi alerte...");
    $alertMsg = "Les erreurs suivantes ont été détectées lors du scraping :\n\n"
              . implode("\n", $errors)
              . "\n\nLes résultats affectés apparaissent avec « — » dans l'email principal.";
    $alertSent = $mailer->sendAlert($alertMsg);
    log_msg($alertSent ? "  → alerte envoyée" : "  → ❌ échec envoi alerte");
}

// ── Email principal ─────────────────────────────────────────────────────────
if (empty($rows1) && empty($rows2)) {
    log_msg("Aucune donnée à envoyer — email annulé.");
} else {
    $body = $builder->buildEmailHtml($rows1, $rows2);
    log_msg("Envoi email à : " . implode(', ', MAIL_TO) . "...");
    $sent = $mailer->sendReport($body);

    if ($sent) {
        log_msg("✅ Email envoyé avec succès");
    } else {
        log_msg("❌ Échec de l'envoi de l'email (voir logs PHP)");
        exit(1);
    }
}

// ── Fin ─────────────────────────────────────────────────────────────────────
$elapsed = round(microtime(true) - $start, 1);
log_msg("Terminé en {$elapsed}s");
log_msg("═══════════════════════════════════════════");

// ── Helper ─────────────────────────────────────────────────────────────────
function log_msg(string $msg): void
{
    $ts = date('Y-m-d H:i:s');
    echo "[{$ts}] {$msg}\n";
}
