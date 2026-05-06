<?php
/**
 * Tire Tracker — Construction des tableaux HTML et des résumés
 *
 * Règle de couleurs :
 *   - Prix le moins cher de la ligne ET appartient à 971pneus → fond VERT
 *   - Prix le moins cher de la ligne ET appartient à un autre site → fond ROUGE
 *   - Ex-æquo → les deux cellules colorées selon leur site respectif
 *
 * Le site de référence ("971pneus") est configurable via $refSite.
 */
class ResultBuilder
{
    /** @var array<string, array> Sites actifs uniquement */
    private array $sites;

    /** Clé du site "favorable" → vert quand il gagne */
    private string $refSite = '971pneus';

    public function __construct(array $sitesConfig)
    {
        $this->sites = array_filter($sitesConfig, fn($s) => !empty($s['active']));
    }

    // ═══════════════════════════════════════════════════════════════════════
    // TABLEAU 1 — Toutes marques (interface web)
    // ═══════════════════════════════════════════════════════════════════════

    public function buildTable1Html(array $rows): string
    {
        if (empty($rows)) {
            return '<p class="text-gray-400 italic py-4">Aucune donnée — ajoutez des dimensions dans "Gérer les listes".</p>';
        }

        $keys = array_keys($this->sites);
        $html = $this->openTable();

        // En-tête
        $html .= '<thead><tr>';
        $html .= '<th class="px-4 py-3 bg-gray-800 text-white text-left rounded-tl-lg">Dimension</th>';
        foreach ($keys as $k) {
            $col   = $this->sites[$k]['color_header'];
            $label = $this->esc($this->sites[$k]['label']);
            $html .= "<th class=\"px-3 py-3 text-white text-center text-xs font-semibold\" style=\"background:{$col}\">{$label}<br>Marque</th>";
            $html .= "<th class=\"px-3 py-3 text-white text-center text-xs font-semibold\" style=\"background:{$col}\">Prix TTC</th>";
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $i => $row) {
            $bg    = $i % 2 === 0 ? 'bg-white' : 'bg-slate-50';
            $min   = $this->minPrice($row['results']);
            $html .= "<tr class=\"{$bg} hover:bg-blue-50 transition-colors\">";
            $html .= "<td class=\"px-4 py-3 font-mono font-semibold text-gray-700 text-sm\">{$row['dim']}</td>";
            foreach ($keys as $k) {
                [$brand, $price] = $this->cells($row['results'][$k] ?? null, $k, $min);
                $html .= $brand . $price;
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';
        $html .= $this->summary1($rows, $keys);
        return $html;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // TABLEAU 2 — Par marque (interface web)
    // ═══════════════════════════════════════════════════════════════════════

    public function buildTable2Html(array $rows): string
    {
        if (empty($rows)) {
            return '<p class="text-gray-400 italic py-4">Aucune donnée — ajoutez des combinaisons dans "Gérer les listes".</p>';
        }

        $keys = array_keys($this->sites);
        $html = $this->openTable();

        $html .= '<thead><tr>';
        $html .= '<th class="px-4 py-3 bg-gray-800 text-white text-left">Marque</th>';
        $html .= '<th class="px-4 py-3 bg-gray-800 text-white text-left">Dimension</th>';
        foreach ($keys as $k) {
            $col   = $this->sites[$k]['color_header'];
            $label = $this->esc($this->sites[$k]['label']);
            $html .= "<th class=\"px-3 py-3 text-white text-center text-xs font-semibold\" style=\"background:{$col}\">{$label}<br>Modèle</th>";
            $html .= "<th class=\"px-3 py-3 text-white text-center text-xs font-semibold\" style=\"background:{$col}\">Prix TTC</th>";
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $i => $row) {
            $bg  = $i % 2 === 0 ? 'bg-white' : 'bg-slate-50';
            $min = $this->minPrice($row['results']);
            $html .= "<tr class=\"{$bg} hover:bg-blue-50 transition-colors\">";
            $html .= "<td class=\"px-4 py-3 font-semibold text-gray-700 text-sm\">{$row['marque']}</td>";
            $html .= "<td class=\"px-4 py-3 font-mono text-gray-600 text-sm\">{$row['dim']}</td>";
            foreach ($keys as $k) {
                [$brand, $price] = $this->cells($row['results'][$k] ?? null, $k, $min);
                $html .= $brand . $price;
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';
        $html .= $this->summary2($rows, $keys);
        return $html;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // EMAIL HTML complet (autonome, inline CSS)
    // ═══════════════════════════════════════════════════════════════════════

    public function buildEmailHtml(array $rows1, array $rows2): string
    {
        $tz   = new DateTimeZone(TIMEZONE);
        $date = (new DateTime('now', $tz))->format('d/m/Y à H:i');

        $t1 = $this->emailTable1($rows1);
        $t2 = $this->emailTable2($rows2);

        $refLabel = isset($this->sites[$this->refSite]['label'])
            ? $this->esc($this->sites[$this->refSite]['label'])
            : $this->esc($this->refSite);

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Comparatif pneus – {$date}</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:Arial,sans-serif;background:#f1f5f9;padding:20px;color:#1e293b}
  .wrap{max-width:960px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,.1)}
  .hdr{background:#0f172a;color:#fff;padding:24px 28px}
  .hdr h1{font-size:20px;font-weight:700}
  .hdr .sub{font-size:12px;opacity:.6;margin-top:4px}
  .sec{padding:24px 28px}
  .sec h2{font-size:15px;font-weight:700;color:#374151;border-bottom:2px solid #e5e7eb;padding-bottom:8px;margin-bottom:16px}
  .scroll-tip{font-size:11px;line-height:1.4;color:rgba(255,255,255,.75);margin:12px 0 0;max-width:36em}
  .leg-sec{padding:18px 28px 4px;background:#fff;border-bottom:1px solid #e5e7eb}
  .leg-title{font-size:12px;font-weight:700;color:#374151;margin:0 0 10px}
  .leg-line{font-size:12px;color:#4b5563;margin:8px 0 0;line-height:1.4}
  .leg-line:first-of-type{margin-top:0}
  .leg-sq{display:inline-block;width:14px;height:14px;border-radius:3px;vertical-align:middle;margin-right:8px}
  .leg-g{background:#dcfce7;border:1px solid #86efac}
  .leg-r{background:#fee2e2;border:1px solid #fca5a5}
  .tbl-scroll{display:block;width:100%;max-width:100%;overflow-x:auto;overflow-y:visible;-webkit-overflow-scrolling:touch;margin:0 0 4px}
  table.cmp-table{border-collapse:collapse;font-size:12px;width:auto;min-width:100%;max-width:none}
  th{padding:9px 10px;color:#fff;font-weight:600;text-align:center}
  th.left{text-align:left;background:#374151}
  td{padding:8px 10px;border-bottom:1px solid #f1f5f9;text-align:center;color:#374151}
  td.left{text-align:left;font-family:monospace;font-weight:600}
  tr:nth-child(even) td{background:#f8fafc}
  .g{background:#dcfce7;color:#166534;font-weight:700}
  .r{background:#fee2e2;color:#991b1b;font-weight:700}
  .na{color:#9ca3af;font-style:italic}
  .err{color:#ef4444;font-size:11px}
  .sum{background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:14px 18px;margin-top:16px}
  .sum h3{font-size:13px;font-weight:700;margin-bottom:8px;color:#374151}
  .sum li{font-size:12px;color:#4b5563;margin-bottom:3px;list-style:none;padding-left:12px;position:relative}
  .sum li::before{content:"•";position:absolute;left:0}
  .ftr{background:#f1f5f9;text-align:center;padding:14px;font-size:11px;color:#64748b}
  @media only screen and (max-width:600px){
    body{padding:12px}
    .hdr{padding:18px 16px}
    .hdr h1{font-size:17px}
    .sec{padding:16px 14px}
    .leg-sec{padding:14px 14px 4px}
    table.cmp-table{font-size:10px}
    .cmp-table th{padding:6px 5px}
    .cmp-table td{padding:5px 5px}
  }
</style>
</head>
<body>
<div class="wrap">
  <div class="hdr">
    <h1>🏁 Comparatif Pneus Guadeloupe</h1>
    <div class="sub">Généré le {$date} (heure Guadeloupe)</div>
    <p class="scroll-tip">↔ Sur téléphone ou écran étroit : faites défiler les tableaux horizontalement pour voir tous les sites (police réduite automatiquement).</p>
  </div>
  <div class="leg-sec">
    <p class="leg-title">Légende — cellule de prix surlignée quand c&apos;est le minimum sur la ligne :</p>
    <p class="leg-line"><span class="leg-sq leg-g" aria-hidden="true"></span><strong>{$refLabel}</strong> moins cher</p>
    <p class="leg-line"><span class="leg-sq leg-r" aria-hidden="true"></span>Autre site moins cher</p>
  </div>
  <div class="sec">
    <h2>📋 Tableau 1 — Meilleur prix toutes marques</h2>
    {$t1}
  </div>
  <div class="sec">
    <h2>🏷️ Tableau 2 — Prix par marque spécifique</h2>
    {$t2}
  </div>
  <div class="ftr">Tire Tracker · edsi.fr/tires · Envoi automatique chaque jour à 5h00</div>
</div>
</body>
</html>
HTML;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Helpers — cellules
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Retourne [cellule_marque, cellule_prix] pour l'interface web (Tailwind).
     */
    private function cells(?array $res, string $siteKey, ?float $minPrice): array
    {
        if ($res === null || !empty($res['error'])) {
            return [
                '<td class="px-3 py-3 text-center text-gray-300 text-xs">—</td>',
                '<td class="px-3 py-3 text-center text-red-400 text-xs font-medium">⚠ erreur</td>',
            ];
        }
        if ($res['price'] === null) {
            return [
                '<td class="px-3 py-3 text-center text-gray-300">—</td>',
                '<td class="px-3 py-3 text-center text-gray-300">—</td>',
            ];
        }

        $isMin = $minPrice !== null && abs($res['price'] - $minPrice) < 0.01;
        $price = number_format($res['price'], 2, ',', ' ') . ' €';
        $brand = $this->esc($res['brand'] ?: '—');

        $priceCls = 'px-3 py-3 text-center text-sm font-semibold';
        if ($isMin) {
            $priceCls .= $siteKey === $this->refSite
                ? ' bg-green-100 text-green-700'
                : ' bg-red-100 text-red-700';
            $price .= $siteKey === $this->refSite ? ' ▼' : ' ▲';
        } else {
            $priceCls .= ' text-gray-700';
        }

        return [
            "<td class=\"px-3 py-3 text-center text-gray-500 text-xs\">{$brand}</td>",
            "<td class=\"{$priceCls}\">{$price}</td>",
        ];
    }

    /**
     * Cellules pour email (inline CSS classes .g / .r / .na).
     */
    private function emailCells(?array $res, string $siteKey, ?float $minPrice): array
    {
        if ($res === null || !empty($res['error'])) {
            return ['<td class="na">—</td>', '<td class="err">⚠ erreur</td>'];
        }
        if ($res['price'] === null) {
            return ['<td class="na">—</td>', '<td class="na">—</td>'];
        }

        $isMin = $minPrice !== null && abs($res['price'] - $minPrice) < 0.01;
        $price = number_format($res['price'], 2, ',', ' ') . ' €';
        $brand = $this->esc($res['brand'] ?: '—');

        $cls = '';
        if ($isMin) {
            $cls = $siteKey === $this->refSite ? 'g' : 'r';
        }

        return [
            "<td>{$brand}</td>",
            "<td class=\"{$cls}\">{$price}</td>",
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Résumés
    // ═══════════════════════════════════════════════════════════════════════

    private function summary1(array $rows, array $keys): string
    {
        ['wins' => $wins, 'missing' => $missing, 'brands' => $brands, 'ties' => $ties]
            = $this->stats1($rows, $keys);

        $total = count($rows);
        $items = [];
        foreach ($keys as $k) {
            $label = $this->esc($this->sites[$k]['label']);
            $items[] = "<strong>{$label}</strong> est le moins cher sur <strong>{$wins[$k]}/{$total}</strong> dimensions";
        }
        if ($ties > 0) $items[] = "<strong>{$ties}</strong> ex-æquo (même prix sur tous les sites)";
        foreach ($keys as $k) {
            if (!empty($missing[$k])) {
                $label = $this->esc($this->sites[$k]['label']);
                $dims  = implode(', ', $missing[$k]);
                $items[] = "<strong>{$label}</strong> n'a pas : {$dims}";
            }
        }
        foreach ($keys as $k) {
            if (!empty($brands[$k])) {
                arsort($brands[$k]);
                $top   = implode(', ', array_slice(array_keys($brands[$k]), 0, 3));
                $label = $this->esc($this->sites[$k]['label']);
                $items[] = "Marques les moins chères chez <strong>{$label}</strong> : {$top}";
            }
        }
        return $this->summaryBox('📊 Résumé', $items);
    }

    private function summary2(array $rows, array $keys): string
    {
        ['wins' => $wins, 'missing' => $missing, 'ties' => $ties]
            = $this->stats2($rows, $keys);

        $total = count($rows);
        $items = [];
        foreach ($keys as $k) {
            $avail = $total - count($missing[$k]);
            $label = $this->esc($this->sites[$k]['label']);
            $items[] = "<strong>{$label}</strong> est moins cher sur <strong>{$wins[$k]}/{$avail}</strong> combinaisons disponibles";
        }
        if ($ties > 0) $items[] = "<strong>{$ties}</strong> ex-æquo";
        foreach ($keys as $k) {
            if (!empty($missing[$k])) {
                $label = $this->esc($this->sites[$k]['label']);
                foreach ($missing[$k] as $item) {
                    $items[] = "<strong>{$label}</strong> ne référence pas : <em>{$item}</em>";
                }
            }
        }
        return $this->summaryBox('📊 Points à noter', $items);
    }

    private function summaryBox(string $title, array $items): string
    {
        $lis = implode('', array_map(fn($i) => "<li class=\"text-sm text-gray-600\">{$i}</li>", $items));
        return <<<HTML
<div class="mt-5 p-4 bg-slate-50 rounded-xl border border-slate-200">
  <h3 class="font-semibold text-gray-700 mb-2">{$title}</h3>
  <ul class="space-y-1">{$lis}</ul>
</div>
HTML;
    }

    // ── Résumés email ───────────────────────────────────────────────────────

    private function emailSummary1(array $rows, array $keys): string
    {
        ['wins' => $wins, 'missing' => $missing, 'brands' => $brands, 'ties' => $ties]
            = $this->stats1($rows, $keys);

        $total = count($rows);
        $items = [];
        foreach ($keys as $k) {
            $items[] = "<strong>{$this->esc($this->sites[$k]['label'])}</strong> moins cher sur <strong>{$wins[$k]}/{$total}</strong> dimensions";
        }
        if ($ties > 0) $items[] = "<strong>{$ties}</strong> ex-æquo";
        foreach ($keys as $k) {
            if (!empty($missing[$k])) {
                $items[] = "<strong>{$this->esc($this->sites[$k]['label'])}</strong> manquant : " . implode(', ', $missing[$k]);
            }
        }
        foreach ($keys as $k) {
            if (!empty($brands[$k])) {
                arsort($brands[$k]);
                $top = implode(', ', array_slice(array_keys($brands[$k]), 0, 3));
                $items[] = "Moins chères chez <strong>{$this->esc($this->sites[$k]['label'])}</strong> : {$top}";
            }
        }
        return $this->emailSummaryBox('Résumé', $items);
    }

    private function emailSummary2(array $rows, array $keys): string
    {
        ['wins' => $wins, 'missing' => $missing, 'ties' => $ties]
            = $this->stats2($rows, $keys);

        $total = count($rows);
        $items = [];
        foreach ($keys as $k) {
            $avail = $total - count($missing[$k]);
            $items[] = "<strong>{$this->esc($this->sites[$k]['label'])}</strong> moins cher sur <strong>{$wins[$k]}/{$avail}</strong> disponibles";
        }
        if ($ties > 0) $items[] = "<strong>{$ties}</strong> ex-æquo";
        foreach ($keys as $k) {
            if (!empty($missing[$k])) {
                foreach ($missing[$k] as $item) {
                    $items[] = "<strong>{$this->esc($this->sites[$k]['label'])}</strong> ne référence pas : <em>{$item}</em>";
                }
            }
        }
        return $this->emailSummaryBox('Points à noter', $items);
    }

    private function emailSummaryBox(string $title, array $items): string
    {
        $lis = implode('', array_map(fn($i) => "<li>{$i}</li>", $items));
        return "<div class=\"sum\"><h3>{$title}</h3><ul>{$lis}</ul></div>";
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Tables email
    // ═══════════════════════════════════════════════════════════════════════

    private function emailTable1(array $rows): string
    {
        if (empty($rows)) return '<p class="na">Aucune donnée.</p>';
        $keys = array_keys($this->sites);

        $html  = '<table class="cmp-table"><thead><tr><th class="left">Dimension</th>';
        foreach ($keys as $k) {
            $col   = $this->sites[$k]['color_header'];
            $label = $this->esc($this->sites[$k]['label']);
            $html .= "<th style=\"background:{$col}\" colspan=\"2\">{$label}</th>";
        }
        $html .= '</tr><tr><th class="left"></th>';
        foreach ($keys as $k) {
            $col = $this->sites[$k]['color_header'];
            $html .= "<th style=\"background:{$col}\">Marque</th><th style=\"background:{$col}\">Prix TTC</th>";
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $min   = $this->minPrice($row['results']);
            $html .= "<tr><td class=\"left\">{$row['dim']}</td>";
            foreach ($keys as $k) {
                [$b, $p] = $this->emailCells($row['results'][$k] ?? null, $k, $min);
                $html .= $b . $p;
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        $sum = $this->emailSummary1($rows, $keys);
        return '<div class="tbl-scroll">' . $html . '</div>' . $sum;
    }

    private function emailTable2(array $rows): string
    {
        if (empty($rows)) return '<p class="na">Aucune donnée.</p>';
        $keys = array_keys($this->sites);

        $html  = '<table class="cmp-table"><thead><tr>';
        $html .= '<th class="left">Marque</th><th class="left">Dimension</th>';
        foreach ($keys as $k) {
            $col   = $this->sites[$k]['color_header'];
            $label = $this->esc($this->sites[$k]['label']);
            $html .= "<th style=\"background:{$col}\" colspan=\"2\">{$label}</th>";
        }
        $html .= '</tr><tr><th class="left"></th><th class="left"></th>';
        foreach ($keys as $k) {
            $col = $this->sites[$k]['color_header'];
            $html .= "<th style=\"background:{$col}\">Modèle</th><th style=\"background:{$col}\">Prix TTC</th>";
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $min   = $this->minPrice($row['results']);
            $html .= "<tr><td class=\"left\">{$row['marque']}</td><td class=\"left\">{$row['dim']}</td>";
            foreach ($keys as $k) {
                [$b, $p] = $this->emailCells($row['results'][$k] ?? null, $k, $min);
                $html .= $b . $p;
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        $sum = $this->emailSummary2($rows, $keys);
        return '<div class="tbl-scroll">' . $html . '</div>' . $sum;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Calculs statistiques
    // ═══════════════════════════════════════════════════════════════════════

    private function stats1(array $rows, array $keys): array
    {
        $wins    = array_fill_keys($keys, 0);
        $missing = array_fill_keys($keys, []);
        $brands  = array_fill_keys($keys, []);
        $ties    = 0;

        foreach ($rows as $row) {
            $min = $this->minPrice($row['results']);
            if ($min === null) continue;

            $winners = [];
            foreach ($keys as $k) {
                $p = $row['results'][$k]['price'] ?? null;
                if ($p !== null && abs($p - $min) < 0.01) $winners[] = $k;
                if ($p === null)                           $missing[$k][] = $row['dim'];
                if (!empty($row['results'][$k]['brand'])) {
                    $b = strtoupper(trim($row['results'][$k]['brand']));
                    $brands[$k][$b] = ($brands[$k][$b] ?? 0) + 1;
                }
            }
            if (count($winners) > 1) { $ties++; foreach ($winners as $w) $wins[$w]++; }
            elseif (count($winners) === 1)              $wins[$winners[0]]++;
        }
        return compact('wins', 'missing', 'brands', 'ties');
    }

    private function stats2(array $rows, array $keys): array
    {
        $wins    = array_fill_keys($keys, 0);
        $missing = array_fill_keys($keys, []);
        $ties    = 0;

        foreach ($rows as $row) {
            $min = $this->minPrice($row['results']);
            if ($min === null) continue;

            $winners = [];
            foreach ($keys as $k) {
                $p = $row['results'][$k]['price'] ?? null;
                if ($p !== null && abs($p - $min) < 0.01) $winners[] = $k;
                if ($p === null)                           $missing[$k][] = "{$row['marque']} {$row['dim']}";
            }
            if (count($winners) > 1) { $ties++; foreach ($winners as $w) $wins[$w]++; }
            elseif (count($winners) === 1)              $wins[$winners[0]]++;
        }
        return compact('wins', 'missing', 'ties');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Micro-helpers
    // ═══════════════════════════════════════════════════════════════════════

    private function minPrice(array $results): ?float
    {
        $prices = array_filter(
            array_map(fn($r) => $r['price'] ?? null, $results),
            fn($p) => $p !== null
        );
        return empty($prices) ? null : min($prices);
    }

    private function openTable(): string
    {
        return '<div class="overflow-x-auto rounded-xl shadow-sm border border-gray-100">'
             . '<table class="min-w-full border-collapse">';
    }

    private function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
