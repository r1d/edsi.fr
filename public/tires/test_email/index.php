<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once SRC_PATH . '/Mailer.php';

// ── Protection par mot de passe (partage la session avec l'app principale) ─
session_name(SESSION_NAME);
session_start();

$authError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === APP_PASSWORD) {
        $_SESSION['authenticated'] = true;
    } else {
        $authError = 'Mot de passe incorrect.';
    }
}
if (!empty($_SESSION['authenticated']) && isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
$isAuth = !empty($_SESSION['authenticated']);

// ── Traitement de l'envoi ──────────────────────────────────────────────────
$sendResult = null;

if ($isAuth && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send'])) {
    date_default_timezone_set(TIMEZONE);

    $toRaw = trim($_POST['to'] ?? '');
    $toList = !empty($toRaw)
        ? array_filter(array_map('trim', explode(',', $toRaw)))
        : MAIL_TO;

    $mailer = new Mailer(BREVO_API_KEY);
    $date   = (new DateTime('now', new DateTimeZone(TIMEZONE)))->format('d/m/Y H:i');
    $from   = MAIL_FROM;
    $fromName = MAIL_FROM_NAME;
    $dest   = implode(', ', $toList);

    $body = '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"></head><body>'
          . '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px">'
          . '<div style="background:#0f172a;color:#fff;padding:20px 24px;border-radius:10px 10px 0 0">'
          . '<h2 style="margin:0;font-size:18px">&#127937; Tire Tracker &mdash; Email de test</h2>'
          . '<p style="margin:4px 0 0;font-size:12px;opacity:.6">' . htmlspecialchars($date) . ' (heure Guadeloupe)</p>'
          . '</div>'
          . '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-top:none;padding:20px 24px;border-radius:0 0 10px 10px">'
          . '<p style="color:#374151;font-size:14px">&#9989; Si vous recevez cet email, la configuration <strong>Brevo</strong> est op&eacute;rationnelle.</p>'
          . '<table style="width:100%;font-size:13px;margin-top:16px;border-collapse:collapse">'
          . '<tr><td style="padding:5px 0;color:#6b7280;width:130px">Exp&eacute;diteur</td><td style="color:#111827">' . htmlspecialchars($fromName . ' <' . $from . '>') . '</td></tr>'
          . '<tr><td style="padding:5px 0;color:#6b7280">Destinataire(s)</td><td style="color:#111827">' . htmlspecialchars($dest) . '</td></tr>'
          . '<tr><td style="padding:5px 0;color:#6b7280">Serveur</td><td style="color:#111827">Brevo API v3</td></tr>'
          . '</table>'
          . '</div></div></body></html>';

    $subject = 'Test Tire Tracker — ' . $date;
    $ok = $mailer->send($subject, $body, array_values($toList));

    $sendResult = [
        'ok'        => $ok,
        'to'        => $dest,
        'time'      => $date,
        'lastError' => $mailer->lastError,
    ];
}

// ── Vue HTML ───────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Test Email — Tire Tracker</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen font-sans antialiased">

<?php if (!$isAuth): ?>
<!-- LOGIN -->
<div class="min-h-screen flex items-center justify-center px-4">
  <div class="w-full max-w-sm">
    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-900 rounded-2xl mb-4">
        <span class="text-3xl">🏁</span>
      </div>
      <h1 class="text-2xl font-bold text-gray-900">Tire Tracker</h1>
      <p class="text-gray-500 text-sm mt-1">Test d'envoi email</p>
    </div>
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
      <form method="POST">
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Mot de passe</label>
        <input type="password" name="password" autofocus required
               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mb-4 focus:outline-none focus:ring-2 focus:ring-blue-500"
               placeholder="••••••••">
        <?php if ($authError): ?>
          <p class="text-red-600 text-sm mb-3"><?= htmlspecialchars($authError) ?></p>
        <?php endif; ?>
        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg">
          Se connecter
        </button>
      </form>
    </div>
  </div>
</div>

<?php else: ?>
<!-- APP -->
<nav class="bg-gray-900 text-white px-6 py-3 flex items-center justify-between">
  <div class="flex items-center gap-3">
    <span class="text-2xl">🏁</span>
    <div>
      <span class="font-bold">Tire Tracker</span>
      <span class="text-gray-400 text-xs ml-2">/ Test email Brevo</span>
    </div>
  </div>
  <div class="flex items-center gap-4">
    <a href="../" class="text-gray-400 hover:text-white text-sm transition-colors">← App principale</a>
    <a href="?logout=1" class="text-gray-400 hover:text-white text-sm transition-colors">Déconnexion</a>
  </div>
</nav>

<div class="max-w-xl mx-auto px-4 py-10">

  <!-- Résultat envoi -->
  <?php if ($sendResult !== null): ?>
    <?php if ($sendResult['ok']): ?>
      <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-5 flex items-start gap-3">
        <span class="text-2xl mt-0.5">✅</span>
        <div>
          <p class="font-semibold text-green-800">Email envoyé avec succès</p>
          <p class="text-green-700 text-sm mt-1">
            Destinataire(s) : <strong><?= htmlspecialchars($sendResult['to']) ?></strong><br>
            Heure : <?= htmlspecialchars($sendResult['time']) ?>
          </p>
        </div>
      </div>
    <?php else: ?>
      <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-5 flex items-start gap-3">
        <span class="text-2xl mt-0.5">❌</span>
        <div>
          <p class="font-semibold text-red-800">Échec de l'envoi</p>
          <p class="text-red-700 text-sm mt-1">Vérifiez la clé API Brevo et l'adresse <code>MAIL_FROM</code> dans <code>config.php</code>.</p>
          <?php if (!empty($sendResult['lastError'])): ?>
            <pre class="mt-3 bg-red-100 text-red-900 text-xs rounded-lg p-3 whitespace-pre-wrap break-all"><?= htmlspecialchars($sendResult['lastError']) ?></pre>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <!-- Formulaire -->
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
    <h1 class="text-xl font-bold text-gray-800 mb-1">Test d'envoi Brevo</h1>
    <p class="text-gray-500 text-sm mb-6">Envoie un email de test via l'API Brevo pour valider la configuration.</p>

    <!-- Config actuelle -->
    <div class="bg-slate-50 rounded-xl p-4 mb-6 text-sm space-y-1.5 border border-slate-100">
      <p class="font-semibold text-gray-600 text-xs uppercase tracking-wide mb-2">Configuration actuelle</p>
      <div class="flex gap-2">
        <span class="text-gray-500 w-28 shrink-0">Expéditeur</span>
        <span class="text-gray-800 font-mono"><?= htmlspecialchars(MAIL_FROM_NAME . ' <' . MAIL_FROM . '>') ?></span>
      </div>
      <div class="flex gap-2">
        <span class="text-gray-500 w-28 shrink-0">Dest. par défaut</span>
        <span class="text-gray-800 font-mono"><?= htmlspecialchars(implode(', ', MAIL_TO)) ?></span>
      </div>
      <div class="flex gap-2">
        <span class="text-gray-500 w-28 shrink-0">Clé API</span>
        <span class="text-gray-800 font-mono">
          <?php
            $key = BREVO_API_KEY;
            echo strlen($key) > 12
                ? htmlspecialchars(substr($key, 0, 12)) . '…'
                : '(non configurée)';
          ?>
        </span>
      </div>
    </div>

    <form method="POST">
      <div class="mb-5">
        <label class="block text-sm font-medium text-gray-700 mb-1.5">
          Destinataire(s) de test
          <span class="text-gray-400 font-normal">(laisser vide = utiliser MAIL_TO de config.php)</span>
        </label>
        <input type="text" name="to"
               value="<?= htmlspecialchars($_POST['to'] ?? '') ?>"
               placeholder="eric@exemple.com, autre@exemple.com"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <p class="text-xs text-gray-400 mt-1">Plusieurs adresses séparées par des virgules.</p>
      </div>

      <button type="submit" name="send" value="1"
              class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg transition-colors">
        ✉️ Envoyer l'email de test
      </button>
    </form>
  </div>

</div>
<?php endif; ?>

</body>
</html>
