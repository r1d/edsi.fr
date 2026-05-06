<?php
/**
 * Tire Tracker — Scraper pour 971pneus.gp
 *
 * Structure HTML connue :
 *   Prix (TTC) : <span class="prix" title="soit XX € HT">47,50 €  <span style="display:none">…</span></span>
 *   On lit uniquement les nœuds texte directs de span.prix (pas les enfants cachés).
 *
 * Les résultats ne sont PAS triés par prix → on scanne tous les prix
 * et on retourne le minimum.
 */
class Pneus971Scraper extends BaseScraper
{
    public function scrapeAll(int $largeur, int $hauteur, int $diametre): array
    {
        $url = $this->buildUrl($this->config['url_all'], $largeur, $hauteur, $diametre);
        return $this->parse($url, '');
    }

    public function scrapeBrand(int $largeur, int $hauteur, int $diametre, string $marque): array
    {
        $url = $this->buildUrl($this->config['url_brand'], $largeur, $hauteur, $diametre, $marque);
        return $this->parse($url, $marque);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function parse(string $url, string $knownBrand): array
    {
        $html = $this->fetch($url);
        $this->delay();

        if ($html === null) {
            return $this->emptyResult(true, $url);
        }

        $xp = $this->htmlToDom($html);

        // ── Récupérer tous les nœuds span.prix ───────────────────────────────
        $priceNodes = $xp->query('//*[contains(@class,"prix")]');
        if ($priceNodes->length === 0) {
            return $this->emptyResult(false, $url);
        }

        // ── Trouver le prix minimum ───────────────────────────────────────────
        $minPrice = PHP_FLOAT_MAX;
        $minIdx   = -1;
        $allPrices = [];

        foreach ($priceNodes as $i => $node) {
            // Lire seulement les nœuds texte directs (ignorer le span caché)
            $rawText = '';
            foreach ($node->childNodes as $child) {
                if ($child->nodeType === XML_TEXT_NODE) {
                    $rawText .= $child->textContent;
                }
            }
            // Fallback : tout le textContent si pas de nœud texte direct
            if (trim($rawText) === '') {
                $rawText = $node->textContent;
            }

            $p = $this->parsePrice($rawText);
            if ($p !== null) {
                $allPrices[$i] = $p;
                if ($p < $minPrice) {
                    $minPrice = $p;
                    $minIdx   = $i;
                }
            }
        }

        if ($minIdx === -1) {
            return $this->emptyResult(false, $url);
        }

        // ── Trouver le nom du produit correspondant ───────────────────────────
        $minNode = $priceNodes->item($minIdx);
        $model   = '';
        $brand   = '';

        // Remonter dans le DOM jusqu'à trouver un titre de produit
        $ancestor = $minNode;
        for ($depth = 0; $depth < 10; $depth++) {
            $ancestor = $ancestor->parentNode;
            if ($ancestor === null || $ancestor->nodeType !== XML_ELEMENT_NODE) break;

            $candidates = $xp->query(
                './/*[contains(@class,"designation")]'
                . '|.//*[contains(@class,"libelle")]'
                . '|.//*[contains(@class,"nom_produit")]'
                . '|.//*[contains(@class,"product-name")]'
                . '|.//*[contains(@class,"marque")]'
                . '|.//h2|.//h3|.//h4',
                $ancestor
            );

            if ($candidates->length > 0) {
                $model = trim(preg_replace('/\s+/', ' ', $candidates->item(0)->textContent));
                break;
            }
        }

        // Marque : connue ou extraite
        $brand = $knownBrand !== ''
            ? strtoupper($knownBrand)
            : ($model !== '' ? $this->extractBrand($model) : '');

        return [
            'price' => $minPrice,
            'brand' => $brand,
            'model' => $model,
            'error' => false,
            'url'   => $url,
        ];
    }
}
