<?php
/**
 * Tire Tracker — Classe de base pour tous les scrapers
 *
 * Chaque scraper de site hérite de cette classe et implémente
 * scrapeAll() et scrapeBrand().
 *
 * Format de retour standardisé :
 *   ['price' => float|null, 'brand' => string, 'model' => string,
 *    'error' => bool,        'url'   => string]
 */
abstract class BaseScraper
{
    protected string $siteKey;
    protected array  $config;

    public function __construct(string $siteKey, array $config)
    {
        $this->siteKey = $siteKey;
        $this->config  = $config;
    }

    // ── Interface à implémenter ────────────────────────────────────────────

    abstract public function scrapeAll(int $largeur, int $hauteur, int $diametre): array;

    abstract public function scrapeBrand(int $largeur, int $hauteur, int $diametre, string $marque): array;

    // ── Construction d'URL ─────────────────────────────────────────────────

    protected function buildUrl(
        string $tpl,
        int    $largeur,
        int    $hauteur,
        int    $diametre,
        string $marque = ''
    ): string {
        return str_replace(
            ['{largeur}', '{hauteur}', '{diametre}', '{marque_lower}', '{marque_upper}'],
            [$largeur,    $hauteur,    $diametre,    strtolower($marque), strtoupper($marque)],
            $tpl
        );
    }

    // ── Requête HTTP ───────────────────────────────────────────────────────

    protected function fetch(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => SCRAPE_TIMEOUT,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 '
                                    . '(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: fr-FR,fr;q=0.9,en;q=0.8',
                'Cache-Control: no-cache',
            ],
        ]);

        $html    = curl_exec($ch);
        $code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errMsg  = curl_error($ch);
        curl_close($ch);

        if ($html === false || $code < 200 || $code >= 400) {
            error_log("[TireTracker:{$this->siteKey}] fetch error HTTP {$code} on {$url} — {$errMsg}");
            return null;
        }
        return $html;
    }

    // ── Parsing DOM ────────────────────────────────────────────────────────

    protected function htmlToDom(string $html): DOMXPath
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        return new DOMXPath($dom);
    }

    // ── Parsing de prix ────────────────────────────────────────────────────

    /**
     * Convertit "65,00 €", "1 234,56 €", "65.00" en float.
     */
    protected function parsePrice(string $raw): ?float
    {
        // Supprimer espaces insécables et ordinaires
        $s = preg_replace('/[\s\x{00A0}]+/u', '', $raw);
        // Garder uniquement chiffres, virgule, point
        $s = preg_replace('/[^\d,.]/', '', $s);
        if ($s === '' || $s === null) return null;

        // Détecter séparateur de milliers : 1.234,56 ou 1,234.56
        if (preg_match('/^\d{1,3}[.,]\d{3}[.,]\d/', $s)) {
            // Supprimer le premier séparateur (milliers)
            $s = preg_replace('/[.,](?=\d{3}[.,])/', '', $s, 1);
        }

        // Normaliser la virgule décimale
        $s = str_replace(',', '.', $s);

        return is_numeric($s) && (float)$s > 0 ? (float)$s : null;
    }

    // ── Extraction de marque depuis un nom de produit ──────────────────────

    /**
     * Extrait la marque (groupe de mots majuscules en début de chaîne).
     * Ex: "BRIDGESTONE TUR6 205/55 16" → "BRIDGESTONE"
     *     "DOUBLE COIN R688 195/65 15" → "DOUBLE COIN"
     */
    protected function extractBrand(string $productName): string
    {
        $name = trim($productName);
        // Groupe de mots tout en majuscules (avec tirets/espaces) en début
        if (preg_match('/^([A-Z][A-Z0-9\s\-]{1,25}?)(?:\s+[A-Z][a-z]|\s+\d|\s*$)/', $name, $m)) {
            return trim($m[1]);
        }
        // Fallback : premier mot
        $parts = explode(' ', $name);
        return strtoupper($parts[0]);
    }

    // ── Pause entre requêtes ───────────────────────────────────────────────

    protected function delay(): void
    {
        if (SCRAPE_DELAY_MS > 0) {
            usleep(SCRAPE_DELAY_MS * 1000);
        }
    }

    // ── Résultat vide normalisé ────────────────────────────────────────────

    protected function emptyResult(bool $error, string $url): array
    {
        return [
            'price' => null,
            'brand' => '',
            'model' => '',
            'error' => $error,
            'url'   => $url,
        ];
    }
}
