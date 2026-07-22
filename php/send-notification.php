<?php
/**
 * Fonction pour envoyer les notifications email RSVP
 * Utilisée après l'enregistrement d'une réponse dans Baserow
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function prepareEmailContent($template, $familyData, $familyCode = '') {
    // Charger le template
    $templatePath = __DIR__ . '/../assets/template/' . $template;
    if (!file_exists($templatePath)) {
        return null;
    }
    
    $content = file_get_contents($templatePath);
    
    // Préparer les remplacements
    $famille = $familyData['famille'] ?? '';
    
    // Mapping des choix pour affichage français
    $choixLabels = [
        'ceremonie' => 'Cérémonie',
        'vinHonneur' => 'Vin d\'honneur',
        'repas' => 'Repas et Soirée',
    ];
    
    // Formater les choix
    $choix = [];
    if (isset($familyData['choix']) && is_array($familyData['choix'])) {
        foreach ($familyData['choix'] as $c) {
            if (is_string($c)) {
                $choix[] = $choixLabels[$c] ?? $c;
            }
        }
    }
    $choixFormatted = implode(', ', $choix);
    
    // Formater les invités confirmés
    $invitesFormatted = '';
    if (isset($familyData['invites'])) {
        $invites = $familyData['invites'];
        if (is_string($invites)) {
            $invites = json_decode($invites, true);
        }
        if (is_array($invites)) {
            $confirmes = [];
            foreach ($invites as $invite) {
                if (isset($invite['statut']) && $invite['statut'] === 'confirme') {
                    $confirmes[] = $invite['prenom'] ?? '';
                }
            }
            $invitesFormatted = implode(', ', $confirmes);
        }
    }
    
    // Formater la nuit
    $nuitFormatted = '';
    if (isset($familyData['nuit'])) {
        $nuitFormatted = ($familyData['nuit'] === true || $familyData['nuit'] === 1) ? 'Oui' : 'Non';
    }
    
    // Effectuer les remplacements
    $replacements = [
        '[famille]' => $famille,
        '[email]' => $familyData['email'] ?? '',
        '[code]' => $familyCode,
        '[choix]' => $choixFormatted,
        '[invites]' => $invitesFormatted,
        '[nuit]' => $nuitFormatted,
        '[consignes]' => $familyData['consignes'] ?? '',
        '[musique]' => $familyData['musique'] ?? '',
        '[commentaire]' => $familyData['commentaire'] ?? '',
    ];
    
    foreach ($replacements as $placeholder => $value) {
        $content = str_replace($placeholder, $value, $content);
    }
    
    return $content;
}

function sendRsvpNotification($familyData, $familyCode = '', $logFile = null) {
    try {
        if ($logFile) {
            file_put_contents($logFile, "- [NOTIFICATION] Début envoi emails\n", FILE_APPEND);
        }
        
        // Charger la config SMTP
        $smtpConfig = require __DIR__ . '/../config/smtp.php';
        
        if ($logFile) {
            file_put_contents($logFile, "- [NOTIFICATION] Config SMTP chargée\n", FILE_APPEND);
        }
        
        $recipientEmail = $familyData['email'] ?? '';
        
        // ===== EMAIL 1 : À l'invité =====
        if (!empty($recipientEmail)) {
            if ($logFile) {
                file_put_contents($logFile, "- [NOTIFICATION] Envoi email à invité: {$recipientEmail}\n", FILE_APPEND);
            }
            
            $mail1 = new PHPMailer(true);
            $mail1->isSMTP();
            $mail1->Host = $smtpConfig['smtp_host'];
            $mail1->Port = $smtpConfig['smtp_port'];
            $mail1->SMTPAuth = true;
            $mail1->Username = $smtpConfig['smtp_user'];
            $mail1->Password = $smtpConfig['smtp_pass'];
            $mail1->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail1->CharSet = 'UTF-8';
            $mail1->Timeout = 10;
            $mail1->SMTPKeepAlive = false;
            
            $mail1->setFrom($smtpConfig['mail_from'], $smtpConfig['mail_from_name']);
            $mail1->addAddress($recipientEmail);
            $mail1->isHTML(false);
            $mail1->Subject = '✓ RSVP reçu : ' . ($familyData['famille'] ?? 'Famille');
            
            // Charger et préparer le contenu depuis le template
            $bodyContent = prepareEmailContent('notification_rsvp.html', $familyData, $familyCode);
            if ($bodyContent) {
                $mail1->Body = $bodyContent;
            } else {
                if ($logFile) {
                    file_put_contents($logFile, "- [NOTIFICATION] ✗ Template notification_rsvp.html non trouvé\n", FILE_APPEND);
                }
            }
            
            $mail1->send();
            
            if ($logFile) {
                file_put_contents($logFile, "- [NOTIFICATION] ✓ Email invité envoyé à: {$recipientEmail}\n", FILE_APPEND);
            }
        }
        
        // ===== EMAIL 2 : À mail_to =====
        if ($logFile) {
            file_put_contents($logFile, "- [NOTIFICATION] Envoi email copie à: {$smtpConfig['mail_to']}\n", FILE_APPEND);
        }
        
        $mail2 = new PHPMailer(true);
        $mail2->isSMTP();
        $mail2->Host = $smtpConfig['smtp_host'];
        $mail2->Port = $smtpConfig['smtp_port'];
        $mail2->SMTPAuth = true;
        $mail2->Username = $smtpConfig['smtp_user'];
        $mail2->Password = $smtpConfig['smtp_pass'];
        $mail2->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail2->CharSet = 'UTF-8';
        $mail2->Timeout = 10;
        $mail2->SMTPKeepAlive = false;
        
        $mail2->setFrom($smtpConfig['mail_from'], $smtpConfig['mail_from_name']);
        $mail2->addAddress($smtpConfig['mail_to']);
        $mail2->isHTML(false);
        $mail2->Subject = '[SUIVI] RSVP reçu : ' . ($familyData['famille'] ?? 'Famille');
        
        // Charger et préparer le contenu depuis le template
        $bodyContent = prepareEmailContent('notification_copie.html', $familyData, $familyCode);
        if ($bodyContent) {
            $mail2->Body = $bodyContent;
        } else {
            if ($logFile) {
                file_put_contents($logFile, "- [NOTIFICATION] ✗ Template notification_copie.html non trouvé\n", FILE_APPEND);
            }
        }
        
        $mail2->send();
        
        if ($logFile) {
            file_put_contents($logFile, "- [NOTIFICATION] ✓ Email copie envoyé à: {$smtpConfig['mail_to']}\n", FILE_APPEND);
        }
        
        return true;
        
    } catch (Exception $e) {
        if ($logFile) {
            file_put_contents($logFile, "- [NOTIFICATION] ✗ Erreur PHPMailer: " . $e->getMessage() . "\n", FILE_APPEND);
        }
        return false;
    } catch (Throwable $e) {
        if ($logFile) {
            file_put_contents($logFile, "- [NOTIFICATION] ✗ Erreur fatale: " . $e->getMessage() . "\n", FILE_APPEND);
        }
        return false;
    }
}
?>
