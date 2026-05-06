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

require_once $root . '/lib/BrevoContactMailer.php';

$esc = static function (string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

date_default_timezone_set('Europe/Paris');
$dateFormatted = (new DateTimeImmutable('now'))->format('d/m/Y \à H:i');

$textBody = "Nouveau contact depuis {$siteLabel}\n\n";
$textBody .= "Nom: {$name}\n";
$textBody .= "Email: {$email}\n";
$textBody .= "Sujet: {$subject}\n\n";
$textBody .= "Message:\n{$message}\n";

$htmlBody = '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"></head><body>'
    . '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px">'
    . '<div style="background:#0f172a;color:#fff;padding:20px 24px;border-radius:10px 10px 0 0">'
    . '<h2 style="margin:0;font-size:18px">Nouveau message — ' . $esc($siteLabel) . '</h2>'
    . '<p style="margin:4px 0 0;font-size:12px;opacity:.6">' . $esc($dateFormatted) . '</p>'
    . '</div>'
    . '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-top:none;padding:20px 24px;border-radius:0 0 10px 10px">'
    . '<table style="width:100%;font-size:14px;border-collapse:collapse">'
    . '<tr><td style="padding:8px 0;color:#6b7280;width:100px;vertical-align:top">Nom</td><td style="color:#111827">' . $esc($name) . '</td></tr>'
    . '<tr><td style="padding:8px 0;color:#6b7280;vertical-align:top">Email</td><td style="color:#111827"><a href="mailto:' . $esc($email) . '">' . $esc($email) . '</a></td></tr>'
    . '<tr><td style="padding:8px 0;color:#6b7280;vertical-align:top">Sujet</td><td style="color:#111827">' . $esc($subject) . '</td></tr>'
    . '</table>'
    . '<div style="margin-top:20px;padding-top:16px;border-top:1px solid #e2e8f0">'
    . '<p style="margin:0 0 8px;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em">Message</p>'
    . '<p style="margin:0;font-size:14px;color:#374151;white-space:pre-wrap">' . $esc($message) . '</p>'
    . '</div></div></div></body></html>';

$mailer = new BrevoContactMailer($brevoKey, $apiBase);
$ok = $mailer->send(
    $notificationSubject,
    $htmlBody,
    [$toEmail],
    $fromEmail,
    $fromName,
    $email,
    $name,
    $textBody,
);

if (!$ok) {
    if ($mailer->lastError !== '') {
        error_log('[edsi:contact] Brevo: ' . $mailer->lastError);
    }
    redirectWithStatus('error');
}

redirectWithStatus('ok');
