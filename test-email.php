<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=utf-8');

echo "=== Test Email SMTP ===\n";

require_once __DIR__ . '/vendor/autoload.php';

echo "✓ Autoloader chargé\n";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    // Charger la config SMTP
    echo "Chargement de la config SMTP...\n";
    $smtpConfig = require __DIR__ . '/config/smtp.php';
    
    echo "✓ Config chargée\n";
    echo "  Host: " . $smtpConfig['smtp_host'] . "\n";
    echo "  Port: " . $smtpConfig['smtp_port'] . "\n";
    echo "  From: " . $smtpConfig['mail_from'] . "\n";
    echo "  To: " . $smtpConfig['mail_to'] . "\n";

    // Créer une instance de PHPMailer
    echo "Création de PHPMailer...\n";
    $mail = new PHPMailer(true);

    // Configuration du serveur SMTP
    $mail->isSMTP();
    $mail->Host = $smtpConfig['smtp_host'];
    $mail->Port = $smtpConfig['smtp_port'];
    $mail->SMTPAuth = true;
    $mail->Username = $smtpConfig['smtp_user'];
    $mail->Password = $smtpConfig['smtp_pass'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // TLS sur port 465
    $mail->CharSet = 'UTF-8';

    echo "✓ Config SMTP appliquée\n";
    echo "Test de connexion SMTP...\n";

    // Destinataires
    $mail->setFrom($smtpConfig['mail_from'], $smtpConfig['mail_from_name']);
    $mail->addAddress($smtpConfig['mail_to']);

    // Contenu
    $mail->isHTML(true);
    $mail->Subject = '✓ Test SMTP - Configuration OK';
    $mail->Body = '<h1>Test d\'envoi SMTP</h1>';
    $mail->Body .= '<p>Cet email de test a été envoyé avec succès !</p>';
    $mail->Body .= '<p>Configuration SMTP validée :</p>';
    $mail->Body .= '<ul>';
    $mail->Body .= '<li>Host : ' . htmlspecialchars($smtpConfig['smtp_host']) . '</li>';
    $mail->Body .= '<li>Port : ' . $smtpConfig['smtp_port'] . '</li>';
    $mail->Body .= '<li>From : ' . htmlspecialchars($smtpConfig['mail_from']) . '</li>';
    $mail->Body .= '<li>To : ' . htmlspecialchars($smtpConfig['mail_to']) . '</li>';
    $mail->Body .= '</ul>';
    $mail->Body .= '<p>Timestamp : ' . date('Y-m-d H:i:s') . '</p>';

    echo "Envoi en cours...\n";
    // Envoyer
    $mail->send();
    
    echo "\n✅ Email envoyé avec succès à : {$smtpConfig['mail_to']}\n";
    echo "From : {$smtpConfig['mail_from']}\n";
    echo "Sujet : {$mail->Subject}\n";
    
} catch (Exception $e) {
    echo "\n❌ Erreur PHPMailer : " . $e->getMessage() . "\n";
    echo "ErrorInfo : {$mail->ErrorInfo}\n";
} catch (Throwable $e) {
    echo "\n❌ Erreur fatale : " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
