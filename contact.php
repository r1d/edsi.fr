<?php
declare(strict_types=1);

const CONTACT_EMAIL = 'hello@edsi.fr';
const RATE_LIMIT_SECONDS = 45;
const MIN_SUBMIT_SECONDS = 4;

function redirectWithStatus(string $status): void
{
    header('Location: /?contact=' . rawurlencode($status) . '#contact');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithStatus('error');
}

$honeypot = trim((string)($_POST['website'] ?? ''));
if ($honeypot !== '') {
    redirectWithStatus('error');
}

$startedAt = (int)($_POST['form_started_at'] ?? 0);
if ($startedAt <= 0 || (time() - $startedAt) < MIN_SUBMIT_SECONDS) {
    redirectWithStatus('error');
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'edsi_contact_rate.json';
$rateData = [];

if (is_file($rateFile)) {
    $raw = file_get_contents($rateFile);
    if ($raw !== false) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $rateData = $decoded;
        }
    }
}

$now = time();
$last = (int)($rateData[$ip] ?? 0);
if ($last > 0 && ($now - $last) < RATE_LIMIT_SECONDS) {
    redirectWithStatus('error');
}

$rateData[$ip] = $now;
@file_put_contents($rateFile, json_encode($rateData, JSON_PRETTY_PRINT));

$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$subject = trim((string)($_POST['subject'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

if ($name === '' || $email === '' || $subject === '' || $message === '') {
    redirectWithStatus('error');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirectWithStatus('error');
}

$name = mb_substr($name, 0, 120);
$subject = mb_substr($subject, 0, 180);
$message = mb_substr($message, 0, 4000);

if (preg_match('/[\r\n]/', $name) || preg_match('/[\r\n]/', $email) || preg_match('/[\r\n]/', $subject)) {
    redirectWithStatus('error');
}

$mailSubject = 'Nouveau message depuis edsi.fr';
$body = "Nouveau contact depuis edsi.fr\n\n";
$body .= "Nom: {$name}\n";
$body .= "Email: {$email}\n";
$body .= "Sujet: {$subject}\n\n";
$body .= "Message:\n{$message}\n";

$headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'From: EDSI Contact <noreply@edsi.fr>',
    'Reply-To: ' . $email,
    'X-Mailer: PHP/' . phpversion(),
];

$sent = @mail(CONTACT_EMAIL, $mailSubject, $body, implode("\r\n", $headers));

if (!$sent) {
    redirectWithStatus('error');
}

redirectWithStatus('ok');
