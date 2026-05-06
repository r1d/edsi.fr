<?php
/**
 * Tire Tracker — Envoi d'emails via l'API transactionnelle Brevo
 * Documentation : https://developers.brevo.com/reference/sendtransacemail
 */
class Mailer
{
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    // ── Email principal (résultats) ────────────────────────────────────────

    public function sendReport(string $htmlBody, ?array $to = null): bool
    {
        $tz      = new DateTimeZone(TIMEZONE);
        $date    = (new DateTime('now', $tz))->format('d/m/Y');
        $subject = str_replace('{date}', $date, MAIL_SUBJECT);

        return $this->send($subject, $htmlBody, $to ?? MAIL_TO);
    }

    // ── Email d'alerte (erreur scraping) ──────────────────────────────────

    public function sendAlert(string $message, ?array $to = null): bool
    {
        $tz   = new DateTimeZone(TIMEZONE);
        $date = (new DateTime('now', $tz))->format('d/m/Y H:i');

        $subject = "⚠ Alerte Tire Tracker — {$date}";
        $html    = <<<HTML
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto">
  <div style="background:#7f1d1d;color:#fff;padding:20px 24px;border-radius:8px 8px 0 0">
    <h2 style="margin:0;font-size:18px">⚠ Alerte Tire Tracker</h2>
    <p style="margin:4px 0 0;font-size:12px;opacity:.8">{$date} (heure Guadeloupe)</p>
  </div>
  <div style="background:#fff;padding:20px 24px;border:1px solid #fecaca;border-radius:0 0 8px 8px">
    <p style="font-size:14px;color:#374151;white-space:pre-line">{$this->esc($message)}</p>
    <p style="font-size:12px;color:#9ca3af;margin-top:16px">
      Les résultats avec erreurs ont quand même été envoyés (affichés avec "—").
    </p>
  </div>
</div>
HTML;

        return $this->send($subject, $html, $to ?? MAIL_TO);
    }

    // ── Appel API Brevo ────────────────────────────────────────────────────

    /** Dernier détail d'erreur (HTTP code + réponse Brevo) */
    public string $lastError = '';

    public function send(string $subject, string $htmlBody, array $toAddresses): bool
    {
        $this->lastError = '';
        $recipients = array_map(fn($email) => ['email' => $email], $toAddresses);

        $payload = json_encode([
            'sender'      => ['name' => MAIL_FROM_NAME, 'email' => MAIL_FROM],
            'to'          => $recipients,
            'subject'     => $subject,
            'htmlContent' => $htmlBody,
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'accept: application/json',
                'content-type: application/json',
                'api-key: ' . $this->apiKey,
            ],
        ]);

        $response = curl_exec($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            $this->lastError = "cURL : {$curlErr}";
            error_log("[TireTracker:Mailer] cURL error: {$curlErr}");
            return false;
        }
        if ($code < 200 || $code >= 300) {
            $this->lastError = "HTTP {$code} — " . $response;
            error_log("[TireTracker:Mailer] Brevo HTTP {$code}: {$response}");
            return false;
        }

        return true;
    }

    private function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
