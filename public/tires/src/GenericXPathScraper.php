<?php
/**
 * Tire Tracker — Scraper générique piloté par XPath/sites.php
 *
 * Tous les sites sont gérés par cette classe : la configuration des
 * sélecteurs (prix, titre/modèle) se trouve dans sites.php sous la clé
 * `selectors`. Pour ajouter un nouveau site concurrent, il suffit donc
 * d'éditer sites.php — aucun nouveau fichier PHP à créer.
 *
 * Format de la clé `selectors` dans sites.php :
 *
 *   'selectors' => [
 *       'price' => [
 *           'xpath'            => '//XPath ciblant les nœuds prix',
 *           'pick'             => 'first' | 'min',
 *               // 'first' : on prend le 1er prix trouvé (utile si l'URL
 *               //          trie déjà par prix croissant côté serveur).
 *               // 'min'   : on scanne tous les nœuds et on retient le
 *               //          minimum.
 *           'direct_text_only' => bool (défaut false),
 *               // true : on ne lit que les nœuds texte DIRECTS du nœud
 *               //        prix (utile quand le site met un span caché
 *               //        à l'intérieur, ex. 971pneus).
 *       ],
 *       'title' => [
 *           'xpath'              => '//XPath du titre/modèle',
 *           'scope'              => 'global' | 'ancestor',
 *               // 'global'   : on cherche dans tout le DOM et on prend
 *               //              le 1er résultat (cas pneudeal — la page
 *               //              est triée par prix donc le 1er titre
 *               //              correspond au 1er prix).
 *               // 'ancestor' : on remonte depuis le nœud prix retenu
 *               //              pour chercher un titre dans son
 *               //              voisinage (cas 971pneus).
 *           'ancestor_max_depth' => int (défaut 10),
 *               // Profondeur max de remontée pour scope = 'ancestor'.
 *       ],
 *   ]
 *
 * Format de retour standardisé (hérité de BaseScraper) :
 *   ['price' => float|null, 'brand' => string, 'model' => string,
 *    'error' => bool,        'url'   => string]
 */
class GenericXPathScraper extends BaseScraper
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

    private function parse(string $url, string $knownBrand): array
    {
        $html = $this->fetch($url);
        $this->delay();

        if ($html === null) {
            return $this->emptyResult(true, $url);
        }

        $xp  = $this->htmlToDom($html);
        $sel = $this->config['selectors'] ?? [];

        $priceXPath  = $sel['price']['xpath']            ?? '//*[contains(@class,"price")]';
        $pickMode    = $sel['price']['pick']             ?? 'first';
        $directText  = !empty($sel['price']['direct_text_only']);

        $priceNodes = $xp->query($priceXPath);
        if ($priceNodes === false || $priceNodes->length === 0) {
            return $this->emptyResult(false, $url);
        }

        [$price, $matchedNode] = $this->pickPrice($priceNodes, $pickMode, $directText);
        if ($price === null) {
            return $this->emptyResult(false, $url);
        }

        $model = $this->extractModel($xp, $sel, $matchedNode);

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

    /**
     * Sélectionne un prix parmi les nœuds candidats selon la stratégie.
     * @return array{0: float|null, 1: \DOMNode|null}
     */
    private function pickPrice(\DOMNodeList $nodes, string $mode, bool $directOnly): array
    {
        if ($mode === 'min') {
            $minPrice = PHP_FLOAT_MAX;
            $minNode  = null;
            foreach ($nodes as $node) {
                $p = $this->parsePrice($this->readPriceText($node, $directOnly));
                if ($p !== null && $p < $minPrice) {
                    $minPrice = $p;
                    $minNode  = $node;
                }
            }
            return $minNode !== null ? [$minPrice, $minNode] : [null, null];
        }

        foreach ($nodes as $node) {
            $p = $this->parsePrice($this->readPriceText($node, $directOnly));
            if ($p !== null) {
                return [$p, $node];
            }
        }
        return [null, null];
    }

    /**
     * Cherche le titre/modèle selon la portée configurée.
     */
    private function extractModel(\DOMXPath $xp, array $sel, ?\DOMNode $priceNode): string
    {
        $titleXPath = $sel['title']['xpath'] ?? '';
        if ($titleXPath === '') return '';

        $scope    = $sel['title']['scope']              ?? 'global';
        $maxDepth = (int)($sel['title']['ancestor_max_depth'] ?? 10);

        if ($scope === 'ancestor' && $priceNode !== null) {
            $node = $priceNode;
            for ($d = 0; $d < $maxDepth; $d++) {
                $node = $node->parentNode;
                if ($node === null || $node->nodeType !== XML_ELEMENT_NODE) break;

                $candidates = $xp->query($titleXPath, $node);
                if ($candidates !== false && $candidates->length > 0) {
                    return trim(preg_replace('/\s+/', ' ', $candidates->item(0)->textContent));
                }
            }
            return '';
        }

        $candidates = $xp->query($titleXPath);
        if ($candidates !== false && $candidates->length > 0) {
            return trim(preg_replace('/\s+/', ' ', $candidates->item(0)->textContent));
        }
        return '';
    }

    /**
     * Lit le texte d'un nœud prix.
     *  - direct_text_only = true  : ne prend que les nœuds texte directs
     *    (ignore les enfants HTML, ex. <span style="display:none"> chez 971pneus).
     *  - direct_text_only = false : textContent complet.
     */
    private function readPriceText(\DOMNode $node, bool $directOnly): string
    {
        if (!$directOnly) {
            return $node->textContent;
        }
        $raw = '';
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $raw .= $child->textContent;
            }
        }
        return trim($raw) !== '' ? $raw : $node->textContent;
    }
}
