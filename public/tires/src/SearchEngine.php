<?php
/**
 * Tire Tracker — Orchestrateur des scrapers
 *
 * Instancie les scrapers actifs et lance les recherches
 * sur les deux tableaux de données.
 */
class SearchEngine
{
    /** @var array<string, array> Configuration complète des sites */
    private array $sites;

    /** @var array<string, BaseScraper> Scrapers instanciés (sites actifs seulement) */
    private array $scrapers = [];

    public function __construct(array $sitesConfig)
    {
        $this->sites = $sitesConfig;

        foreach ($sitesConfig as $key => $cfg) {
            if (empty($cfg['active'])) continue;

            $class = $cfg['scraper'];
            if (!class_exists($class)) {
                error_log("[TireTracker] Scraper introuvable : {$class}");
                continue;
            }
            $this->scrapers[$key] = new $class($key, $cfg);
        }
    }

    // ── Tableau 1 : toutes marques ─────────────────────────────────────────

    /**
     * @param  array $dimensions Lignes issues de Database::getDimensions()
     * @return array Tableau de résultats par dimension
     *
     * Format :
     * [
     *   ['dim'=>'205/55/16', 'largeur'=>205, 'hauteur'=>55, 'diametre'=>16,
     *    'results' => ['pneudeal'=>[...], '971pneus'=>[...]]]
     * ]
     */
    public function runAll(array $dimensions): array
    {
        $rows = [];
        foreach ($dimensions as $d) {
            $row = [
                'dim'      => "{$d['largeur']}/{$d['hauteur']}/{$d['diametre']}",
                'largeur'  => (int) $d['largeur'],
                'hauteur'  => (int) $d['hauteur'],
                'diametre' => (int) $d['diametre'],
                'results'  => [],
            ];
            foreach ($this->scrapers as $key => $scraper) {
                $row['results'][$key] = $scraper->scrapeAll(
                    (int) $d['largeur'],
                    (int) $d['hauteur'],
                    (int) $d['diametre']
                );
            }
            $rows[] = $row;
        }
        return $rows;
    }

    // ── Tableau 2 : par marque ─────────────────────────────────────────────

    /**
     * @param  array $dimensionsMarques Lignes issues de Database::getDimensionsMarques()
     * @return array Tableau de résultats par (marque, dimension)
     *
     * Format :
     * [
     *   ['dim'=>'205/55/16', 'marque'=>'BRIDGESTONE', ...,
     *    'results' => ['pneudeal'=>[...], '971pneus'=>[...]]]
     * ]
     */
    public function runBranded(array $dimensionsMarques): array
    {
        $rows = [];
        foreach ($dimensionsMarques as $d) {
            $row = [
                'dim'      => "{$d['largeur']}/{$d['hauteur']}/{$d['diametre']}",
                'marque'   => $d['marque'],
                'largeur'  => (int) $d['largeur'],
                'hauteur'  => (int) $d['hauteur'],
                'diametre' => (int) $d['diametre'],
                'results'  => [],
            ];
            foreach ($this->scrapers as $key => $scraper) {
                $row['results'][$key] = $scraper->scrapeBrand(
                    (int) $d['largeur'],
                    (int) $d['hauteur'],
                    (int) $d['diametre'],
                    $d['marque']
                );
            }
            $rows[] = $row;
        }
        return $rows;
    }

    // ── Utilitaires ────────────────────────────────────────────────────────

    /** Retourne uniquement les sites actifs */
    public function getActiveSites(): array
    {
        return array_filter($this->sites, fn($s) => !empty($s['active']));
    }

    /**
     * Collecte les erreurs de scraping (site inaccessible)
     * à travers les deux tableaux de résultats.
     *
     * @return string[] Messages d'erreur lisibles
     */
    public function collectErrors(array ...$rowSets): array
    {
        $errors = [];
        foreach ($rowSets as $rows) {
            foreach ($rows as $row) {
                foreach ($row['results'] as $siteKey => $res) {
                    if (!empty($res['error'])) {
                        $label   = $this->sites[$siteKey]['label'] ?? $siteKey;
                        $context = isset($row['marque'])
                            ? "{$row['dim']} ({$row['marque']})"
                            : $row['dim'];
                        $errors[] = "[{$label}] Site inaccessible pour {$context}";
                    }
                }
            }
        }
        return array_unique($errors);
    }
}
