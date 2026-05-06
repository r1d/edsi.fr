<?php
/**
 * Tire Tracker — Interface web principale
 * Login + Gérer les listes + Lancer la recherche + Résultats
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';

session_name(SESSION_NAME);
session_start();

// ── Logout ─────────────────────────────────────────────────────────────────
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// ── Login POST ─────────────────────────────────────────────────────────────
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === APP_PASSWORD) {
        $_SESSION['authenticated'] = true;
        header('Location: index.php');
        exit;
    }
    $loginError = 'Mot de passe incorrect.';
}

$isAuth = !empty($_SESSION['authenticated']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tire Tracker</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: { brand: '#1D4ED8' }
        }
      }
    }
  </script>
  <style>
    [x-cloak] { display: none !important; }
    .tab-active   { @apply border-b-2 border-blue-600 text-blue-600 font-semibold; }
    .tab-inactive { @apply text-gray-500 hover:text-gray-700 hover:border-gray-300 border-b-2 border-transparent; }
    .btn-primary  { @apply bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed; }
    .btn-danger   { @apply bg-red-500 hover:bg-red-600 text-white text-xs px-2.5 py-1 rounded transition-colors; }
    .btn-ghost    { @apply text-gray-400 hover:text-gray-600 transition-colors text-sm px-2 py-1 rounded; }
    .badge-on     { @apply bg-green-100 text-green-700 text-xs font-medium px-2 py-0.5 rounded-full; }
    .badge-off    { @apply bg-gray-100 text-gray-500 text-xs font-medium px-2 py-0.5 rounded-full; }
    .input-field  { @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full; }
    .card         { @apply bg-white rounded-2xl shadow-sm border border-gray-100 p-6; }
    /* Spinner */
    @keyframes spin { to { transform: rotate(360deg); } }
    .spinner { animation: spin 1s linear infinite; }
  </style>
</head>
<body class="bg-slate-100 min-h-screen font-sans antialiased">

<?php if (!$isAuth): ?>
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!-- PAGE DE LOGIN                                                          -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<div class="min-h-screen flex items-center justify-center px-4">
  <div class="w-full max-w-sm">

    <!-- Logo / titre -->
    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-900 rounded-2xl mb-4 shadow-lg">
        <span class="text-3xl">🏁</span>
      </div>
      <h1 class="text-2xl font-bold text-gray-900">Tire Tracker</h1>
      <p class="text-gray-500 text-sm mt-1">Comparatif pneus Guadeloupe</p>
    </div>

    <!-- Card login -->
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
      <form method="POST" autocomplete="off">
        <div class="mb-5">
          <label class="block text-sm font-medium text-gray-700 mb-1.5" for="password">
            Mot de passe
          </label>
          <input
            type="password"
            id="password"
            name="password"
            autofocus
            class="input-field text-base"
            placeholder="••••••••"
            required
          >
          <?php if ($loginError): ?>
            <p class="mt-2 text-sm text-red-600 flex items-center gap-1">
              <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
              </svg>
              <?= htmlspecialchars($loginError) ?>
            </p>
          <?php endif; ?>
        </div>
        <button type="submit" class="btn-primary w-full py-2.5 text-base">
          Se connecter
        </button>
      </form>
    </div>

    <p class="text-center text-xs text-gray-400 mt-6">edsi.fr/tires</p>
  </div>
</div>

<?php else: ?>
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!-- APPLICATION PRINCIPALE                                                  -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->

<!-- BARRE DE NAVIGATION -->
<nav class="bg-gray-900 text-white shadow-lg sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-6 py-3 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <span class="text-2xl">🏁</span>
      <div>
        <span class="font-bold text-lg leading-none">Tire Tracker</span>
        <span class="text-gray-400 text-xs block leading-none mt-0.5">Comparatif pneus Guadeloupe</span>
      </div>
    </div>
    <a href="?logout=1"
       class="flex items-center gap-1.5 text-gray-400 hover:text-white text-sm transition-colors px-3 py-1.5 rounded-lg hover:bg-gray-800">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
      </svg>
      Déconnexion
    </a>
  </div>
</nav>

<!-- CONTENU PRINCIPAL -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-6">

  <!-- ONGLETS -->
  <div class="border-b border-gray-200 mb-6">
    <nav class="flex gap-1 -mb-px" id="tabs">
      <button onclick="switchTab('manage')"  id="tab-manage"
              class="tab-active px-5 py-3 text-sm transition-all">
        📋 Gérer les listes
      </button>
      <button onclick="switchTab('run')"     id="tab-run"
              class="tab-inactive px-5 py-3 text-sm transition-all">
        ▶ Lancer la recherche
      </button>
      <button onclick="switchTab('results')" id="tab-results"
              class="tab-inactive px-5 py-3 text-sm transition-all">
        📊 Résultats
        <span id="results-badge" class="hidden ml-1.5 bg-blue-600 text-white text-xs rounded-full px-1.5 py-0.5">✓</span>
      </button>
    </nav>
  </div>

  <!-- ═══════════════════════════════════════════════════════════════════ -->
  <!-- ONGLET 1 — GÉRER LES LISTES                                        -->
  <!-- ═══════════════════════════════════════════════════════════════════ -->
  <div id="content-manage">
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

      <!-- ── Tableau 1 : Dimensions ─────────────────────────────────── -->
      <div class="card">
        <div class="flex items-center justify-between mb-5">
          <div>
            <h2 class="text-base font-bold text-gray-800">Tableau 1 — Toutes marques</h2>
            <p class="text-xs text-gray-500 mt-0.5">Meilleur prix disponible pour chaque dimension</p>
          </div>
          <span class="text-2xl">🔍</span>
        </div>

        <!-- Formulaire d'ajout -->
        <form onsubmit="addDim(event)" class="flex gap-2 mb-5">
          <input type="text" id="new-dim" placeholder="205/55/16"
                 class="input-field font-mono" pattern="\d+/\d+/\d+" required>
          <button type="submit" class="btn-primary whitespace-nowrap px-4">
            + Ajouter
          </button>
        </form>
        <p id="dim-error" class="text-red-500 text-xs mb-3 hidden"></p>

        <!-- Liste -->
        <div id="dims-list" class="space-y-1.5">
          <div class="text-center text-gray-400 text-sm py-4">Chargement…</div>
        </div>
      </div>

      <!-- ── Tableau 2 : Dimensions + Marques ──────────────────────── -->
      <div class="card">
        <div class="flex items-center justify-between mb-5">
          <div>
            <h2 class="text-base font-bold text-gray-800">Tableau 2 — Par marque</h2>
            <p class="text-xs text-gray-500 mt-0.5">Prix d'une marque précise sur une dimension</p>
          </div>
          <span class="text-2xl">🏷️</span>
        </div>

        <!-- Formulaire d'ajout -->
        <form onsubmit="addDm(event)" class="mb-5">
          <div class="flex gap-2 mb-2">
            <input type="text" id="new-dm-dim" placeholder="225/45/17"
                   class="input-field font-mono" pattern="\d+/\d+/\d+" required>
            <input type="text" id="new-dm-marque" placeholder="BRIDGESTONE"
                   class="input-field uppercase" required maxlength="30">
          </div>
          <button type="submit" class="btn-primary w-full">+ Ajouter</button>
        </form>
        <p id="dm-error" class="text-red-500 text-xs mb-3 hidden"></p>

        <!-- Liste -->
        <div id="dm-list" class="space-y-1.5">
          <div class="text-center text-gray-400 text-sm py-4">Chargement…</div>
        </div>
      </div>

    </div><!-- /grid -->
  </div><!-- /content-manage -->

  <!-- ═══════════════════════════════════════════════════════════════════ -->
  <!-- ONGLET 2 — LANCER LA RECHERCHE                                      -->
  <!-- ═══════════════════════════════════════════════════════════════════ -->
  <div id="content-run" class="hidden">
    <div class="max-w-lg mx-auto">
      <div class="card text-center">

        <div class="mb-6">
          <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-50 rounded-2xl mb-4">
            <span class="text-3xl">🔎</span>
          </div>
          <h2 class="text-lg font-bold text-gray-800">Lancer une recherche</h2>
          <p class="text-gray-500 text-sm mt-1">
            Scrape les deux sites et affiche les résultats.<br>
            <span class="text-xs text-gray-400">(Pas d'envoi email — utilisez le cron pour l'email quotidien)</span>
          </p>
        </div>

        <!-- Stats -->
        <div class="flex justify-center gap-4 mb-6" id="run-stats">
          <div class="bg-slate-50 rounded-xl px-5 py-3 text-center border border-slate-100">
            <div class="text-2xl font-bold text-gray-800" id="stat-t1">—</div>
            <div class="text-xs text-gray-500">dimensions actives</div>
          </div>
          <div class="bg-slate-50 rounded-xl px-5 py-3 text-center border border-slate-100">
            <div class="text-2xl font-bold text-gray-800" id="stat-t2">—</div>
            <div class="text-xs text-gray-500">combinaisons actives</div>
          </div>
        </div>

        <!-- Bouton + loader -->
        <button id="btn-run" onclick="runSearch()" class="btn-primary px-10 py-3 text-base w-full mb-4">
          ▶ Lancer la recherche
        </button>

        <!-- Progress (caché par défaut) -->
        <div id="run-progress" class="hidden">
          <div class="flex items-center justify-center gap-3 py-4">
            <svg class="spinner w-6 h-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <div class="text-left">
              <p class="text-gray-700 font-medium text-sm">Scraping en cours…</p>
              <p class="text-gray-400 text-xs" id="run-timer">0s écoulées</p>
            </div>
          </div>
          <p class="text-xs text-gray-400">Cette opération peut prendre 1 à 3 minutes selon le nombre d'entrées.</p>
        </div>

        <!-- Message succès/erreur -->
        <div id="run-result" class="hidden mt-2"></div>

        <!-- Alertes erreurs scraping -->
        <div id="run-errors" class="hidden mt-4 text-left bg-amber-50 border border-amber-200 rounded-xl p-4">
          <p class="text-amber-800 font-semibold text-sm mb-2">⚠ Erreurs détectées</p>
          <ul id="run-errors-list" class="text-amber-700 text-xs space-y-1"></ul>
        </div>

      </div>
    </div>
  </div><!-- /content-run -->

  <!-- ═══════════════════════════════════════════════════════════════════ -->
  <!-- ONGLET 3 — RÉSULTATS                                                -->
  <!-- ═══════════════════════════════════════════════════════════════════ -->
  <div id="content-results" class="hidden">

    <!-- État vide -->
    <div id="results-empty" class="card text-center py-16">
      <div class="text-5xl mb-4">📊</div>
      <p class="text-gray-500">Aucun résultat pour l'instant.</p>
      <p class="text-gray-400 text-sm mt-1">
        Lancez une recherche depuis l'onglet <strong>Lancer la recherche</strong>.
      </p>
    </div>

    <!-- Résultats (remplis dynamiquement) -->
    <div id="results-content" class="hidden space-y-8">

      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-lg font-bold text-gray-800">Résultats de la recherche</h2>
          <p class="text-gray-400 text-xs mt-0.5" id="results-date"></p>
        </div>
        <div class="flex items-center gap-3 text-xs">
          <span class="flex items-center gap-1">
            <span class="inline-block w-4 h-4 bg-green-100 border border-green-300 rounded"></span>
            <span class="text-gray-600">971pneus.gp moins cher</span>
          </span>
          <span class="flex items-center gap-1">
            <span class="inline-block w-4 h-4 bg-red-100 border border-red-300 rounded"></span>
            <span class="text-gray-600">Autre site moins cher</span>
          </span>
        </div>
      </div>

      <div class="card">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
          <span class="text-lg">🔍</span> Tableau 1 — Meilleur prix toutes marques
        </h3>
        <div id="result-table1"></div>
      </div>

      <div class="card">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
          <span class="text-lg">🏷️</span> Tableau 2 — Prix par marque spécifique
        </h3>
        <div id="result-table2"></div>
      </div>

    </div>
  </div><!-- /content-results -->

</div><!-- /max-w-7xl -->
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!-- JAVASCRIPT                                                              -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<?php if ($isAuth):
    // Calcule le chemin URL depuis DOCUMENT_ROOT — fiable sous Herd/nginx et PHP built-in
    $docRoot  = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $selfDir  = realpath(dirname(__FILE__));
    $relative = $docRoot ? str_replace('\\', '/', str_replace($docRoot, '', $selfDir)) : '';
    $ajaxUrl  = $relative . '/ajax.php';
?>
<script>
const AJAX_URL = <?= json_encode($ajaxUrl) ?>;
// ── Onglets ─────────────────────────────────────────────────────────────────
const TABS = ['manage', 'run', 'results'];
let currentTab = 'manage';

function switchTab(name) {
  TABS.forEach(t => {
    document.getElementById('content-' + t).classList.toggle('hidden', t !== name);
    const btn = document.getElementById('tab-' + t);
    btn.className = btn.className.replace(/tab-(active|inactive)/g, '');
    btn.classList.add(t === name ? 'tab-active' : 'tab-inactive');
    btn.classList.add('px-5', 'py-3', 'text-sm', 'transition-all');
  });
  currentTab = name;
  if (name === 'run') refreshRunStats();
}

// ── Requête AJAX générique ────────────────────────────────────────────────────
async function ajax(action, data = {}) {
  const form = new FormData();
  form.append('action', action);
  Object.entries(data).forEach(([k, v]) => form.append(k, v));
  const res  = await fetch(AJAX_URL, { method: 'POST', body: form });
  const text = await res.text();
  try {
    return JSON.parse(text);
  } catch {
    throw new Error('Réponse PHP invalide : ' + text.substring(0, 300));
  }
}

// ═══════════════════════════════════════════════════════════════════════════
// TABLEAU 1 — Dimensions
// ═══════════════════════════════════════════════════════════════════════════

async function loadDims() {
  const { ok, data } = await ajax('list_dims');
  if (!ok) return;
  renderList('dims-list', data, renderDimRow, 'dim');
}

function renderDimRow(item) {
  const active = item.active == 1;
  return `
    <div class="flex items-center justify-between px-3 py-2 rounded-lg border ${active ? 'border-gray-100 bg-white' : 'border-dashed border-gray-200 bg-gray-50 opacity-60'} group" id="dim-row-${item.id}">
      <div class="flex items-center gap-2.5">
        <span class="font-mono text-sm font-semibold text-gray-700">${item.largeur}/${item.hauteur}/${item.diametre}</span>
        <span class="${active ? 'badge-on' : 'badge-off'}">${active ? 'actif' : 'inactif'}</span>
      </div>
      <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
        <button onclick="toggleDim(${item.id})" class="btn-ghost" title="${active ? 'Désactiver' : 'Activer'}">
          ${active ? '⏸' : '▶'}
        </button>
        <button onclick="deleteDim(${item.id}, '${item.largeur}/${item.hauteur}/${item.diametre}')" class="btn-danger">
          ✕
        </button>
      </div>
    </div>`;
}

async function addDim(e) {
  e.preventDefault();
  const dim = document.getElementById('new-dim').value.trim();
  showError('dim-error', '');
  const res = await ajax('add_dim', { dim });
  if (!res.ok) { showError('dim-error', res.error); return; }
  document.getElementById('new-dim').value = '';
  loadDims();
}

async function toggleDim(id) {
  await ajax('toggle_dim', { id });
  loadDims();
}

async function deleteDim(id, label) {
  if (!confirm(`Supprimer la dimension ${label} ?`)) return;
  await ajax('delete_dim', { id });
  loadDims();
}

// ═══════════════════════════════════════════════════════════════════════════
// TABLEAU 2 — Dimensions + Marques
// ═══════════════════════════════════════════════════════════════════════════

async function loadDm() {
  const { ok, data } = await ajax('list_dm');
  if (!ok) return;
  renderList('dm-list', data, renderDmRow, 'dm');
}

function renderDmRow(item) {
  const active = item.active == 1;
  return `
    <div class="flex items-center justify-between px-3 py-2 rounded-lg border ${active ? 'border-gray-100 bg-white' : 'border-dashed border-gray-200 bg-gray-50 opacity-60'} group" id="dm-row-${item.id}">
      <div class="flex items-center gap-2.5">
        <span class="text-sm font-bold text-gray-700 min-w-[90px]">${item.marque}</span>
        <span class="font-mono text-sm text-gray-600">${item.largeur}/${item.hauteur}/${item.diametre}</span>
        <span class="${active ? 'badge-on' : 'badge-off'}">${active ? 'actif' : 'inactif'}</span>
      </div>
      <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
        <button onclick="toggleDm(${item.id})" class="btn-ghost" title="${active ? 'Désactiver' : 'Activer'}">
          ${active ? '⏸' : '▶'}
        </button>
        <button onclick="deleteDm(${item.id}, '${item.marque} ${item.largeur}/${item.hauteur}/${item.diametre}')" class="btn-danger">
          ✕
        </button>
      </div>
    </div>`;
}

async function addDm(e) {
  e.preventDefault();
  const dim    = document.getElementById('new-dm-dim').value.trim();
  const marque = document.getElementById('new-dm-marque').value.trim().toUpperCase();
  showError('dm-error', '');
  const res = await ajax('add_dm', { dim, marque });
  if (!res.ok) { showError('dm-error', res.error); return; }
  document.getElementById('new-dm-dim').value    = '';
  document.getElementById('new-dm-marque').value = '';
  loadDm();
}

async function toggleDm(id) {
  await ajax('toggle_dm', { id });
  loadDm();
}

async function deleteDm(id, label) {
  if (!confirm(`Supprimer ${label} ?`)) return;
  await ajax('delete_dm', { id });
  loadDm();
}

// ═══════════════════════════════════════════════════════════════════════════
// LANCEMENT DE LA RECHERCHE
// ═══════════════════════════════════════════════════════════════════════════

let runTimer = null;

async function refreshRunStats() {
  const [d1, d2] = await Promise.all([ajax('list_dims'), ajax('list_dm')]);
  if (d1.ok) {
    const active = (d1.data || []).filter(r => r.active == 1).length;
    document.getElementById('stat-t1').textContent = active;
  }
  if (d2.ok) {
    const active = (d2.data || []).filter(r => r.active == 1).length;
    document.getElementById('stat-t2').textContent = active;
  }
}

async function runSearch() {
  const btn      = document.getElementById('btn-run');
  const progress = document.getElementById('run-progress');
  const result   = document.getElementById('run-result');
  const errors   = document.getElementById('run-errors');
  const timer    = document.getElementById('run-timer');

  btn.disabled = true;
  progress.classList.remove('hidden');
  result.classList.add('hidden');
  errors.classList.add('hidden');

  let elapsed = 0;
  runTimer = setInterval(() => {
    elapsed++;
    timer.textContent = elapsed + 's écoulées';
  }, 1000);

  try {
    // ── 1. Lancer le job en arrière-plan (retour immédiat) ──────────────────
    const start = await ajax('run');
    if (!start.ok) throw new Error(start.error || 'Impossible de lancer le job');

    const jobId = start.jobId;

    // ── 2. Poller toutes les 2,5s jusqu'à completion ────────────────────────
    let res = null;
    while (true) {
      await new Promise(r => setTimeout(r, 2500));
      res = await ajax('poll', { jobId });

      if (res.status === 'done')  break;
      if (res.status === 'error') throw new Error(res.error || 'Erreur dans le worker');
      if (res.status === 'unknown') throw new Error('Job introuvable (expiré ?)');
      // status === 'running' → on continue
    }

    clearInterval(runTimer);
    progress.classList.add('hidden');

    // ── 3. Afficher les résultats ────────────────────────────────────────────
    result.innerHTML = `<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm flex items-center gap-2">
      <span class="text-lg">✅</span>
      <span>Recherche terminée en ${elapsed}s — <button onclick="switchTab('results')" class="underline font-semibold">Voir les résultats →</button></span>
    </div>`;
    result.classList.remove('hidden');

    if (res.errors && res.errors.length > 0) {
      document.getElementById('run-errors-list').innerHTML =
        res.errors.map(e => `<li>• ${escHtml(e)}</li>`).join('');
      errors.classList.remove('hidden');
    }

    document.getElementById('result-table1').innerHTML  = res.table1;
    document.getElementById('result-table2').innerHTML  = res.table2;
    document.getElementById('results-date').textContent = 'Dernière mise à jour : ' + res.runDate;
    document.getElementById('results-empty').classList.add('hidden');
    document.getElementById('results-content').classList.remove('hidden');
    document.getElementById('results-badge').classList.remove('hidden');

  } catch (err) {
    clearInterval(runTimer);
    progress.classList.add('hidden');
    result.innerHTML = `<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-sm space-y-1">
      <p class="font-semibold">❌ Erreur lors de la recherche</p>
      <p class="font-mono text-xs break-all">${escHtml(String(err.message || err))}</p>
    </div>`;
    result.classList.remove('hidden');
  } finally {
    btn.disabled = false;
  }
}

// ═══════════════════════════════════════════════════════════════════════════
// UTILITAIRES
// ═══════════════════════════════════════════════════════════════════════════

function renderList(containerId, items, rowFn, type) {
  const el = document.getElementById(containerId);
  if (!items || items.length === 0) {
    el.innerHTML = `<p class="text-gray-400 text-sm text-center py-6 border-2 border-dashed border-gray-100 rounded-xl">
      Aucune entrée. Ajoutez une ${type === 'dim' ? 'dimension' : 'combinaison'} ci-dessus.
    </p>`;
    return;
  }
  el.innerHTML = items.map(rowFn).join('');
}

function showError(id, msg) {
  const el = document.getElementById(id);
  if (msg) { el.textContent = msg; el.classList.remove('hidden'); }
  else      { el.textContent = '';  el.classList.add('hidden'); }
}

function escHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
    .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// ── Init ────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  loadDims();
  loadDm();
});
</script>
<?php endif; ?>

</body>
</html>
