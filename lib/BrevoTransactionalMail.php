<?php
declare(strict_types=1);

/**
 * Envoi d’e-mails transactionnels via l’API Brevo (https://developers.brevo.com).
 * Module autonome réutilisable : aucune dépendance Composer, uniquement ext-curl.
 */
final class BrevoTransactionalMail
{
    private const DEFAULT_API_BASE = 'https://api.brevo.com/v3';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $apiBaseUrl = self::DEFAULT_API_BASE,
    ) {
        if ($this->apiKey === '') {
            throw new InvalidArgumentException('Clé API Brevo vide.');
        }
    }

    /**
     * @param array{
     *   from_email: string,
     *   from_name?: string,
     *   to: list<array{email: string, name?: string}>,
     *   reply_to_email?: string,
     *   reply_to_name?: string,
     *   subject: string,
     *   text_body: string,
     *   html_body?: string|null,
     * } $message
     *
     * @return array{ok: bool, http_code: int, response_body: string}
     */
    public function send(array $message): array
    {
        $url = rtrim($this->apiBaseUrl, '/') . '/smtp/email';

        $sender = ['email' => $message['from_email']];
        if (($message['from_name'] ?? '') !== '') {
            $sender['name'] = $message['from_name'];
        }

        $payload = [
            'sender' => $sender,
            'to' => array_map(
                static function (array $r): array {
                    $row = ['email' => $r['email']];
                    if (isset($r['name']) && $r['name'] !== '') {
                        $row['name'] = $r['name'];
                    }

                    return $row;
                },
                $message['to'],
            ),
            'subject' => $message['subject'],
            'textContent' => $message['text_body'],
        ];

        if (!empty($message['html_body'])) {
            $payload['htmlContent'] = $message['html_body'];
        }

        if (!empty($message['reply_to_email'])) {
            $payload['replyTo'] = ['email' => $message['reply_to_email']];
            if (!empty($message['reply_to_name'])) {
                $payload['replyTo']['name'] = $message['reply_to_name'];
            }
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return ['ok' => false, 'http_code' => 0, 'response_body' => 'curl_init failed'];
        }

        $json = json_encode($payload, JSON_THROW_ON_ERROR);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'api-key: ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);

        $responseBody = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ($responseBody === false) {
            $responseBody = curl_error($ch) ?: 'curl_exec failed';
        }
        curl_close($ch);

        $ok = $httpCode >= 200 && $httpCode < 300;

        return [
            'ok' => $ok,
            'http_code' => $httpCode,
            'response_body' => is_string($responseBody) ? $responseBody : '',
        ];
    }
}
