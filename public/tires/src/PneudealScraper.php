<?php
/**
 * Tire Tracker — Scraper pour pneudeal.gp
 *
 * Structure HTML connue :
 *   Prix    : <span class="price">65,00&nbsp;€</span>
 *   Produit : élément avec class contenant "product-title" ou "product-name"
 *
 * Les résultats sont déjà triés par prix croissant grâce au paramètre
 * ?order=product.price.asc — on prend donc simplement le premier.
 */
class PneudealScraper extends BaseScraper
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

        // ── Prix ─────────────────────────────────────────────────────────────
        // Exclure les anciens prix barrés (.old-price, .regular-price, etc.)
        $priceNodes = $xp->query(
            '//*[
                contains(concat(" ",normalize-space(@class)," ")," price ")
                and not(contains(@class,"old"))
                and not(contains(@class,"regular"))
                and not(contains(@class,"without"))
            ]'
        );

        if ($priceNodes->length === 0) {
            return $this->emptyResult(false, $url); // aucun résultat
        }

        $price = null;
        foreach ($priceNodes as $node) {
            $p = $this->parsePrice($node->textContent);
            if ($p !== null) {
                $price = $p;
                break; // premier = le moins cher (déjà trié)
            }
        }

        // ── Modèle / Marque ───────────────────────────────────────────────────
        $model = '';
        $titleNodes = $xp->query(
            '//*[contains(@class,"product-title")]'
            . '|//*[contains(@class,"product_title")]'
            . '|//*[contains(@class,"product-name")]'
            . '|//*[contains(@class,"product_name")]'
            . '|//*[contains(@class,"item-title")]'
        );

        if ($titleNodes->length > 0) {
            $model = trim(preg_replace('/\s+/', ' ', $titleNodes->item(0)->textContent));
        }

        // La marque est soit connue (recherche par marque) soit extraite du titre
        $brand = $knownBrand !== ''
            ? strtoupper($knownBrand)
            : ($model !== '' ? $this->extractBrand($model) : '');

        return [
            'price' => $price,
            'brand' => $brand,
            'model' => $model,
            'error' => false,
            'url'   => $url,
        ];
    }
}
