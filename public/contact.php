<?php
declare(strict_types=1);

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

if (mb_strlen($email) > 180 || str_contains($email, "\0")) {
    redirectWithStatus('error');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirectWithStatus('error');
}

if (preg_match('/[\x00-\x1F\x7F]/', $email) === 1) {
    redirectWithStatus('error');
}

$name = mb_substr($name, 0, 120);
$subject = mb_substr($subject, 0, 180);
$message = mb_substr($message, 0, 4000);

if (preg_match('/[\r\n]/', $name) || preg_match('/[\r\n]/', $email) || preg_match('/[\r\n]/', $subject)) {
    redirectWithStatus('error');
}

$root = dirname(__DIR__);
$configPath = $root . '/config.php';
if (!is_file($configPath)) {
    redirectWithStatus('error');
}

/** @var mixed $config */
$config = require $configPath;
if (!is_array($config)) {
    redirectWithStatus('error');
}

$brevoKey = trim((string)($config['brevo']['api_key'] ?? ''));
$apiBase = trim((string)($config['brevo']['api_base_url'] ?? 'https://api.brevo.com/v3'));
$fromEmail = trim((string)($config['contact_mail']['from_email'] ?? ''));
$fromName = trim((string)($config['contact_mail']['from_name'] ?? ''));
$toEmail = trim((string)($config['contact_mail']['to_email'] ?? ''));
$notificationSubject = trim((string)($config['contact_mail']['notification_subject'] ?? ''));
$siteLabel = trim((string)($config['contact_mail']['site_label'] ?? 'site'));

if ($brevoKey === '' || $fromEmail === '' || $toEmail === '' || $notificationSubject === '' || $siteLabel === '') {
    redirectWithStatus('error');
}

if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
    redirectWithStatus('error');
}

if (!function_exists('curl_init')) {
    redirectWithStatus('error');
}

require_once $root . '/lib/BrevoTransactionalMail.php';

$body = "Nouveau contact depuis {$siteLabel}\n\n";
$body .= "Nom: {$name}\n";
$body .= "Email: {$email}\n";
$body .= "Sujet: {$subject}\n\n";
$body .= "Message:\n{$message}\n";

try {
    $mailer = new BrevoTransactionalMail($brevoKey, $apiBase);
    $result = $mailer->send([
        'from_email' => $fromEmail,
        'from_name' => $fromName,
        'to' => [['email' => $toEmail]],
        'reply_to_email' => $email,
        'reply_to_name' => $name,
        'subject' => $notificationSubject,
        'text_body' => $body,
    ]);
} catch (Throwable) {
    redirectWithStatus('error');
}

if (!$result['ok']) {
    redirectWithStatus('error');
}

redirectWithStatus('ok');
