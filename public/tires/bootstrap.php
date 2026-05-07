<?php
declare(strict_types=1);

/**
 * Tire Tracker — bootstrap commun (web + cli)
 * - charge config.php
 * - sinon affiche une erreur explicite (au lieu d'un 500 "muet")
 */

$configPath = __DIR__ . '/config.php';

if (!is_file($configPath) || !is_readable($configPath)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');

    $hint = "Configuration manquante.\n\n"
        . "Le fichier `public/tires/config.php` n'est pas présent (ou non lisible) en production.\n"
        . "Copiez `public/tires/config.example.php` vers `public/tires/config.php` et renseignez les valeurs.\n";

    // En CLI, on sort simplement avec un code d'erreur.
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $hint);
        exit(1);
    }

    // En web, on affiche le message (ça évite de devoir aller fouiller les logs pour un oubli de fichier).
    echo $hint;
    exit;
}

require_once $configPath;

