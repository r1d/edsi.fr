<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'EDSI - Etudes et Développement de Solutions Informatiques';
$metaDescription = $metaDescription ?? 'EDSI conçoit des sites vitrines rapides, des boutiques en ligne sous licence et des solutions web sur mesure.';
$canonicalUrl = $canonicalUrl ?? 'https://edsi.fr/';
$ogImage = $ogImage ?? 'https://edsi.fr/images/edsi-143x59.png';
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
  <meta name="robots" content="index,follow,max-image-preview:large">
  <meta name="language" content="fr">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="fr_FR">
  <meta property="og:title" content="<?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:image" content="<?= htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
  <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>">
  <link rel="icon" type="image/x-icon" href="/images/favicon_package_v0.16/favicon.ico">
  <link rel="apple-touch-icon" href="/images/favicon_package_v0.16/apple-touch-icon.png">
  <link rel="manifest" href="/images/favicon_package_v0.16/site.webmanifest">
  <link rel="stylesheet" href="/main.css?v=<?= filemtime(__DIR__ . '/../main.css') ?>">
</head>
<body>
  <header class="site-header">
    <div class="container nav">
      <a href="/" class="brand" aria-label="Retour à l'accueil EDSI">
        <img src="/images/edsi-143x59.png" alt="Logo EDSI">
      </a>
      <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-menu" aria-label="Ouvrir le menu">
        <span class="burger-line"></span>
        <span class="burger-line"></span>
        <span class="burger-line"></span>
      </button>
      <nav class="menu" id="site-menu" aria-label="Navigation principale">
        <a href="/#services">Services</a>
        <a href="/#projet-type">Produits</a>
        <a href="/#contact">Contact</a>
      </nav>
    </div>
  </header>
