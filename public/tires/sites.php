<?php
/**
 * Tire Tracker — Déclaration des sites concurrents
 *
 * Pour ajouter un nouveau site :
 *   1. Dupliquer un bloc ci-dessous
 *   2. Changer la clé (ex: 'nouveausite')
 *   3. Renseigner label, scraper (nom de classe), couleur, URL patterns
 *   4. Créer src/NouveauSiteScraper.php en étendant BaseScraper
 *   5. Passer active à true
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
        'scraper'      => 'PneudealScraper',
        'color_header' => '#1D4ED8',           // bleu
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
    ],

    '971pneus' => [
        'label'        => '971pneus.gp',
        'active'       => true,
        'scraper'      => 'Pneus971Scraper',
        'color_header' => '#6D28D9',           // violet
        'url_all'      => 'https://971pneus.gp/?type=Auto'
                        . '&largeur={largeur}&hauteur={hauteur}&diametre={diametre}'
                        . '&runflatp=&marquep=&vitesse=&charge=&promo=',
        'url_brand'    => 'https://971pneus.gp/?type=Auto'
                        . '&largeur={largeur}&hauteur={hauteur}&diametre={diametre}'
                        . '&runflatp=&marquep={marque_upper}&vitesse=&charge=&promo=',
    ],

    // ── Exemple : futur concurrent ──────────────────────────────────────────
    // 'nouveausite' => [
    //     'label'        => 'nouveausite.gp',
    //     'active'       => false,
    //     'scraper'      => 'NouveauSiteScraper',
    //     'color_header' => '#065F46',
    //     'url_all'   => 'https://nouveausite.gp/pneus?l={largeur}&h={hauteur}&d={diametre}&sort=price',
    //     'url_brand' => 'https://nouveausite.gp/pneus?l={largeur}&h={hauteur}&d={diametre}&brand={marque_upper}&sort=price',
    // ],

];
