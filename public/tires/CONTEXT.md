# Tire Tracker — Contexte pour reprise

## Stack
- PHP 8.4, SQLite (PDO), Tailwind CDN
- Hébergé sur VPS sous nginx + PHP-FPM, et en local via Laravel Herd (edsi.fr.test)
- Brevo API v3 pour les emails transactionnels
- Pas de framework

## Arborescence

```
/tires/
├── config.php          — constantes (APP_PASSWORD, BREVO_API_KEY, MAIL_*, SCRAPE_*, TIMEZONE, DB_PATH, SRC_PATH)
├── config.local.php    — clé Brevo (non versionné, chargé par config.php si présent)
├── sites.php           — retourne array de config des sites à scraper (pneudeal, 971pneus)
├── index.php           — UI principale (login + 3 onglets : Gérer / Lancer / Résultats)
├── ajax.php            — endpoint AJAX (CRUD dimensions, run, poll)
├── run_job.php         — worker CLI lancé en arrière-plan par ajax.php via exec()
├── run.php             — CLI cron (scraping + envoi email)
├── tire_tracker.db     — SQLite
├── test_email/
│   └── index.php       — page de test d'envoi Brevo
└── src/
    ├── Database.php
    ├── BaseScraper.php
    ├── GenericXPathScraper.php   ← scraper unique piloté par sites.php (clé `selectors`)
    ├── SearchEngine.php
    ├── ResultBuilder.php
    └── Mailer.php
```

## Problème principal : le scraping ne se termine jamais

### Symptôme
- Cliquer "Lancer la recherche" dans l'UI → spinner infini (250s+, jamais de résultat)
- Sur VPS et en local (Herd) : même comportement

### Architecture du job
1. `ajax.php?action=run` → génère un `$jobId`, écrit `/tmp/tt_{jobId}.json` avec `{"status":"running"}`, lance `run_job.php` via `exec()` en arrière-plan, retourne `{ok:true, jobId}`
2. Le JS poll `ajax.php?action=poll&jobId=xxx` toutes les 2.5s jusqu'à `status=done`
3. `run_job.php` fait le scraping, écrit le résultat dans le même fichier JSON

### Diagnostic actuel
- Aucun fichier `/tmp/tt_*.json` jamais créé
- `exec()` fonctionne depuis le CLI PHP, mais **pas depuis PHP-FPM** (nginx)
- La commande exec utilisée :
  ```php
  exec("{$phpBin} {$script} {$arg} > {$logFile} 2>&1 &");
  ```
- Hypothèse : sous PHP-FPM, le `&` ne détache pas vraiment le process, ou `exec()` est bloquant

### Code de debug actuel dans ajax.php (case 'run')
Le case `run` contient un `sleep(1)` et retourne un bloc `debug` dans le JSON — **à nettoyer une fois le problème résolu**.

## Pistes de solution

### Option A — `fastcgi_finish_request()` (recommandée, pas d'exec)
Envoyer la réponse HTTP immédiatement, puis continuer le traitement dans le même process :
```php
case 'run':
    $jobId   = 'job_' . bin2hex(random_bytes(8));
    $jobFile = sys_get_temp_dir() . '/tt_' . $jobId . '.json';
    file_put_contents($jobFile, json_encode(['status' => 'running']));

    ob_end_clean();
    echo json_encode(['ok' => true, 'jobId' => $jobId]);

    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }

    ignore_user_abort(true);
    set_time_limit(0);

    // Inline du run_job.php ici (ou require)
    // ...
    break;
```

### Option B — `nohup` + stdin fermé
```php
$cmd = sprintf(
    'nohup %s %s %s < /dev/null > %s 2>&1 & echo $!',
    escapeshellarg(PHP_BINARY),
    escapeshellarg(__DIR__ . '/run_job.php'),
    escapeshellarg($jobId),
    escapeshellarg($logFile)
);
$pid = trim(shell_exec($cmd));
```

### Option C — Cron / queue
Stocker la demande en DB, un cron toutes les minutes traite la file.

## Autres points résolus
- **AJAX URL** : calculée côté PHP avec `DOCUMENT_ROOT` vs `__FILE__` pour gérer le sous-dossier sous Herd/nginx (`/tires/ajax.php` et non `/ajax.php`)
- **Timeout nginx 504** : c'est précisément pour ça que le background job a été introduit
- **Brevo** : clé API v3 (`xkeysib-…`) dans `config.local.php`, expéditeur `hello@edsi.fr`
- **Scraping** : `SCRAPE_TIMEOUT=10s`, `SCRAPE_DELAY_MS=400` — avec 1 dimension active + 1 marque, 4 requêtes max

## Variables config importantes
```php
define('SCRAPE_TIMEOUT',  10);
define('SCRAPE_DELAY_MS', 400);
define('TIMEZONE', 'America/Guadeloupe');
define('DB_PATH',  __DIR__ . '/tire_tracker.db');
define('SRC_PATH', __DIR__ . '/src');
```
