<?php
declare(strict_types=1);

/**
 * Envoi transactionnel Brevo pour edsi.fr (aucune dépendance vers un autre dossier du dépôt).
 * POST https://api.brevo.com/v3/smtp/email — logique cURL alignée sur l’API officielle.
 *
 * @see https://developers.brevo.com/reference/sendtransacemail
 */
final class BrevoContactMailer
{
    public string $lastError = '';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $apiBaseUrl = 'https://api.brevo.com/v3',
    ) {
    }

    /**
     * @param list<string> $toAddresses
     */
    public function send(
        string $subject,
        string $htmlBody,
        array $toAddresses,
        string $fromEmail,
        string $fromName,
        ?string $replyToEmail = null,
        ?string $replyToName = null,
        ?string $textBody = null,
    ): bool {
        $this->lastError = '';
        $recipients = array_map(static fn (string $email): array => ['email' => $email], $toAddresses);

        $sender = ['email' => $fromEmail];
        if ($fromName !== '') {
            $sender['name'] = $fromName;
        }

        $payload = [
            'sender' => $sender,
            'to' => $recipients,
            'subject' => $subject,
            'htmlContent' => $htmlBody,
        ];

        if ($textBody !== null && $textBody !== '') {
            $payload['textContent'] = $textBody;
        }

        if ($replyToEmail !== null && $replyToEmail !== '') {
            $payload['replyTo'] = ['email' => $replyToEmail];
            if ($replyToName !== null && $replyToName !== '') {
                $payload['replyTo']['name'] = $replyToName;
            }
        }

        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($payloadJson === false) {
            $this->lastError = 'Échec encodage JSON du message.';
            return false;
        }

        $url = rtrim($this->apiBaseUrl, '/') . '/smtp/email';
        $ch = curl_init($url);
        if ($ch === false) {
            $this->lastError = 'curl_init a échoué.';
            return false;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payloadJson,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'accept: application/json',
                'content-type: application/json',
                'api-key: ' . $this->apiKey,
            ],
        ]);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr !== '') {
            $this->lastError = "cURL : {$curlErr}";
            error_log('[edsi:BrevoContactMailer] cURL error: ' . $curlErr);

            return false;
        }

        if ($code < 200 || $code >= 300) {
            $this->lastError = 'HTTP ' . $code . ' — ' . (is_string($response) ? $response : '');
            error_log('[edsi:BrevoContactMailer] Brevo HTTP ' . $code . ': ' . (is_string($response) ? $response : ''));

            return false;
        }

        return true;
    }
}
