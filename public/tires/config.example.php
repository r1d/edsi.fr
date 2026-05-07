<?php
declare(strict_types=1);

/**
 * Tire Tracker — copier ce fichier vers config.php et renseigner les valeurs.
 * config.php n’est pas versionné (comme à la racine du site).
 */

// ─── SÉCURITÉ ──────────────────────────────────────────────────────────────
define('APP_PASSWORD',  'changeme_ici');
define('SESSION_NAME',  'tire_tracker');

// ─── BREVO (API transactionnelle) ───────────────────────────────────────────
define('BREVO_API_KEY',  'xkeysib-REMPLACER');
define('MAIL_FROM',      'hello@edsi.fr');
define('MAIL_FROM_NAME', 'Tire Tracker');
define('MAIL_TO', [
    'vous@exemple.com',
    // 'lautre@exemple.com',
]);
define('MAIL_SUBJECT', '🛞 Comparatif pneus Guadeloupe – {date}');

// ─── SCRAPING ───────────────────────────────────────────────────────────────
define('SCRAPE_TIMEOUT',  10);
define('SCRAPE_DELAY_MS', 400);

// ─── APPLICATION ────────────────────────────────────────────────────────────
define('TIMEZONE', 'America/Guadeloupe');
define('DB_PATH',  __DIR__ . '/tire_tracker.db');
define('SRC_PATH', __DIR__ . '/src');
