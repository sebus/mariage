<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Dépendances manquantes. Lancez composer install.']);
    exit;
}

require $autoloadPath;

$repoConfigPath = __DIR__ . '/../config/smtp.php';
$repoConfig = [];
if (file_exists($repoConfigPath)) {
    $loadedConfig = require $repoConfigPath;
    if (is_array($loadedConfig)) {
        $repoConfig = $loadedConfig;
    }
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = filter_var((string) ($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$message = trim((string) ($_POST['message'] ?? ''));

if ($name === '' || $email === false || $message === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Champs invalides']);
    exit;
}

$smtpHost = getenv('SMTP_HOST') ?: (string) ($repoConfig['smtp_host'] ?? '');
$smtpPort = (int) (getenv('SMTP_PORT') ?: ($repoConfig['smtp_port'] ?? 587));
$smtpUser = getenv('SMTP_USER') ?: (string) ($repoConfig['smtp_user'] ?? '');
$smtpPass = getenv('SMTP_PASS') ?: (string) ($repoConfig['smtp_pass'] ?? '');
$mailFrom = getenv('MAIL_FROM') ?: (string) ($repoConfig['mail_from'] ?? $smtpUser);
$mailFromName = getenv('MAIL_FROM_NAME') ?: (string) ($repoConfig['mail_from_name'] ?? 'Formulaire Mariage');
$mailTo = getenv('MAIL_TO') ?: (string) ($repoConfig['mail_to'] ?? $mailFrom);

if ($smtpHost === '' || $smtpUser === '' || $smtpPass === '' || $mailTo === '') {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Configuration SMTP incomplète']);
    exit;
}

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $smtpPort;
    $mail->CharSet = 'UTF-8';

    $mail->setFrom($mailFrom, $mailFromName);
    $mail->addAddress($mailTo);
    $mail->addReplyTo((string) $email, $name);

    $mail->Subject = 'Nouveau message depuis le site mariage';
    $mail->Body = "Nom: {$name}\nEmail: {$email}\n\nMessage:\n{$message}";

    $mail->send();

    echo json_encode(['ok' => true, 'message' => 'Message envoyé']);
} catch (Exception $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Erreur envoi email',
        'error' => $exception->getMessage(),
    ]);
}
