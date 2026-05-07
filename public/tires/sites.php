<?php
/**
 * Tire Tracker — Déclaration des sites concurrents
 *
 * Pour ajouter un nouveau site (sans écrire un seul fichier PHP) :
 *   1. Dupliquer un bloc ci-dessous
 *   2. Changer la clé (ex: 'nouveausite')
 *   3. Renseigner label, couleur, URL patterns
 *   4. Inspecter le HTML du site cible pour repérer les classes du prix
 *      et du titre/modèle, puis remplir le bloc 'selectors' (voir
 *      src/GenericXPathScraper.php pour la doc complète des clés)
 *   5. Passer active à true
 *
 * Le scraper par défaut est 'GenericXPathScraper' : aucune classe PHP
 * spécifique n'est nécessaire tant que le site reste parsable en XPath.
 *
 * Placeholders disponibles dans les URL :
 *   {largeur}       ex: 205
 *   {hauteur}       ex: 55
 *   {diametre}      ex: 16
 *   {marque_lower}  ex: bridgestone
 *   {marque_upper}  ex: BRIDGESTONE
 */
return [

    'pneudeal' => [
        'label'        => 'pneudeal.gp',
        'active'       => true,
        'scraper'      => 'GenericXPathScraper',
        'color_header' => '#c88ec8',           // bleu
        'url_all'      => 'https://www.pneudeal.gp/14-auto/s-7'
                        . '/hauteur_auto-{hauteur}'
                        . '/diametre_auto-{diametre}'
                        . '/largeur_auto-{largeur}'
                        . '?order=product.price.asc',
        'url_brand'    => 'https://www.pneudeal.gp/14-auto/s-7'
                        . '/hauteur_auto-{hauteur}'
                        . '/diametre_auto-{diametre}'
                        . '/largeur_auto-{largeur}'
                        . '/marque_2-{marque_lower}'
                        . '?order=product.price.asc',
        // L'URL trie déjà par prix asc : on prend simplement le 1er prix.
        'selectors'    => [
            'price' => [
                'xpath'            => '//*[contains(concat(" ",normalize-space(@class)," ")," price ")'
                                    . ' and not(contains(@class,"old"))'
                                    . ' and not(contains(@class,"regular"))'
                                    . ' and not(contains(@class,"without"))]',
                'pick'             => 'first',
                'direct_text_only' => false,
            ],
            'title' => [
                'xpath' => '//*[contains(@class,"product-title")]'
                         . '|//*[contains(@class,"product_title")]'
                         . '|//*[contains(@class,"product-name")]'
                         . '|//*[contains(@class,"product_name")]'
                         . '|//*[contains(@class,"item-title")]',
                'scope' => 'global',
            ],
        ],
    ],

    '971pneus' => [
        'label'        => '971pneus.gp',
        'active'       => true,
        'scraper'      => 'GenericXPathScraper',
        'color_header' => '#81b47f',           // violet
        'url_all'      => 'https://971pneus.gp/?type=Auto'
                        . '&largeur={largeur}&hauteur={hauteur}&diametre={diametre}'
                        . '&runflatp=&marquep=&vitesse=&charge=&promo=',
        'url_brand'    => 'https://971pneus.gp/?type=Auto'
                        . '&largeur={largeur}&hauteur={hauteur}&diametre={diametre}'
                        . '&runflatp=&marquep={marque_upper}&vitesse=&charge=&promo=',
        // Pas de tri par URL : on scanne et on retient le min.
        // direct_text_only = true → ignore le <span style="display:none">
        // qui contient le prix HT à l'intérieur de span.prix.
        'selectors'    => [
            'price' => [
                'xpath'            => '//*[contains(@class,"prix")]',
                'pick'             => 'min',
                'direct_text_only' => true,
            ],
            'title' => [
                'xpath' => './/*[contains(@class,"designation")]'
                         . '|.//*[contains(@class,"libelle")]'
                         . '|.//*[contains(@class,"nom_produit")]'
                         . '|.//*[contains(@class,"product-name")]'
                         . '|.//*[contains(@class,"marque")]'
                         . '|.//h2|.//h3|.//h4',
                'scope'              => 'ancestor',
                'ancestor_max_depth' => 10,
            ],
        ],
    ],

    // ── Exemple : futur concurrent ──────────────────────────────────────────
    // 'nouveausite' => [
    //     'label'        => 'nouveausite.gp',
    //     'active'       => false,
    //     'scraper'      => 'GenericXPathScraper',
    //     'color_header' => '#298fcf',
    //     'url_all'   => 'https://nouveausite.gp/pneus?l={largeur}&h={hauteur}&d={diametre}&sort=price',
    //     'url_brand' => 'https://nouveausite.gp/pneus?l={largeur}&h={hauteur}&d={diametre}&brand={marque_upper}&sort=price',
    //     'selectors' => [
    //         'price' => [
    //             'xpath'            => '//*[contains(@class,"product-price")]',
    //             'pick'             => 'first',   // ou 'min' si pas de tri par URL
    //             'direct_text_only' => false,     // true si <span> caché à l'intérieur
    //         ],
    //         'title' => [
    //             'xpath'              => '//*[contains(@class,"product-title")]',
    //             'scope'              => 'global',  // ou 'ancestor'
    //             'ancestor_max_depth' => 10,
    //         ],
    //     ],
    // ],

];
