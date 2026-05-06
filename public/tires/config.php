<?php
/**
 * Tire Tracker — Configuration
 * Remplissez toutes les valeurs avant le premier lancement.
 */

// ─── SÉCURITÉ ──────────────────────────────────────────────────────────────
define('APP_PASSWORD',  't1r3s');   // mot de passe interface web
define('SESSION_NAME',  'tire_tracker');

// ─── BREVO (API transactionnelle) ───────────────────────────────────────────
// Clé : copier config.local.php.example → config.local.php (non versionné), ou export BREVO_API_KEY
if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}
if (!defined('BREVO_API_KEY')) {
    define('BREVO_API_KEY', (string) (getenv('BREVO_API_KEY') ?: ''));
}
define('MAIL_FROM',      'hello@edsi.fr');
define('MAIL_FROM_NAME', 'Tire Tracker');
define('MAIL_TO', [
    'eric.degoul@gmail.com',
    // 'olivier.preteseille@gmail.com',
]);
define('MAIL_SUBJECT', 'Comparatif pneus Guadeloupe – {date}');

// ─── SCRAPING ───────────────────────────────────────────────────────────────
define('SCRAPE_TIMEOUT',  10);    // secondes max par requête cURL
define('SCRAPE_DELAY_MS', 400);   // pause entre requêtes (ms) — évite le ban

// ─── APPLICATION ────────────────────────────────────────────────────────────
define('TIMEZONE', 'America/Guadeloupe');
define('DB_PATH',  __DIR__ . '/tire_tracker.db');
define('SRC_PATH', __DIR__ . '/src');
