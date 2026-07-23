<?php
/**
 * Endpoint pour récupérer les données du dashboard
 * GET /php/get-dashboard-data.php
 * Retourne les données RSVP formatées pour le tableau
 */

session_start();
header('Content-Type: application/json');

// Vérifier l'authentification
if (!isset($_SESSION['dashboard_authenticated']) || $_SESSION['dashboard_authenticated'] !== true) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Non authentifié']);
    exit;
}

// Vérifier le timeout de session (30 minutes)
if (isset($_SESSION['dashboard_auth_time'])) {
    $timeout = 30 * 60; // 30 minutes
    if (time() - $_SESSION['dashboard_auth_time'] > $timeout) {
        unset($_SESSION['dashboard_authenticated']);
        http_response_code(401);
        echo json_encode(['ok' => false, 'message' => 'Session expirée']);
        exit;
    }
    // Réinitialiser le timer
    $_SESSION['dashboard_auth_time'] = time();
}

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require $autoloadPath;
}

// Charger les variables d'environnement
$dotenvPath = __DIR__ . '/../.env';
if (file_exists($dotenvPath)) {
    $dotenv = Dotenv\Dotenv::createMutable(__DIR__ . '/..');
    $dotenv->load();
}

$baserowApiUrl = $_ENV['BASEROW_API_URL'] ?? getenv('BASEROW_API_URL');
$baserowToken = $_ENV['BASEROW_TOKEN'] ?? getenv('BASEROW_TOKEN');
$baserowTableId = $_ENV['BASEROW_TABLE_ID'] ?? getenv('BASEROW_TABLE_ID');

if (!$baserowApiUrl || !$baserowToken || !$baserowTableId) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Configuration Baserow manquante']);
    exit;
}

try {
    $client = new GuzzleHttp\Client();
    
    // Récupérer toutes les données (avec pagination)
    $page = 1;
    $allResults = [];
    
    do {
        $response = $client->get("{$baserowApiUrl}/database/rows/table/{$baserowTableId}/", [
            'headers' => [
                'Authorization' => "Token {$baserowToken}",
                'Content-Type' => 'application/json',
            ],
            'query' => [
                'user_field_names' => 'true',
                'page' => $page,
                'size' => 100,
            ],
        ]);
        
        $data = json_decode($response->getBody()->getContents(), true);
        
        if (isset($data['results'])) {
            $allResults = array_merge($allResults, $data['results']);
        }
        
        // Vérifier s'il y a d'autres pages
        $page++;
        
        // Limite de sécurité : 10 pages max
        if ($page > 10) break;
        
        // Si on a moins de 100 résultats sur cette page, c'est la dernière
        if (!isset($data['results']) || count($data['results']) < 100) {
            break;
        }
    } while (true);
    
    // Formater les résultats
    $formatted = [];
    $totals = [
        'total_familles' => 0,
        'confirmations_oui' => 0,
        'confirmations_non' => 0,
        'confirmations_attente' => 0,
        'total_personnes' => 0,
        'ceremonie_oui' => 0,
        'vin_honneur_oui' => 0,
        'repas_oui' => 0,
        'nuit_oui' => 0,
    ];
    
    foreach ($allResults as $row) {
        $famille = $row['famille'] ?? 'N/A';
        $email = $row['email'] ?? '';
        $confirmation = $row['confirmation'] ?? null;
        $dateConfirmation = $row['dateConfirmation'] ?? '';
        $nuit = $row['nuit'] ?? false;
        $invites = [];
        $choix = [];
        
        // Parser le JSON des invites
        if (isset($row['invites']) && !empty($row['invites'])) {
            $invitesData = is_string($row['invites']) ? json_decode($row['invites'], true) : $row['invites'];
            if (is_array($invitesData)) {
                $invites = $invitesData;
            }
        }
        
        // Compter les personnes confirmées et récupérer leurs noms
        $nbPersonnes = 0;
        $invitesList = [];
        foreach ($invites as $invite) {
            if (is_array($invite) && isset($invite['statut']) && $invite['statut'] === 'confirme') {
                $nbPersonnes++;
                if (isset($invite['prenom'])) {
                    $invitesList[] = $invite['prenom'];
                }
            }
        }
        
        // Parser les choix (multi-select Baserow)
        $hasCeremonie = false;
        $hasVinHonneur = false;
        $hasRepas = false;
        
        if (isset($row['choix']) && !empty($row['choix'])) {
            $choixData = is_array($row['choix']) ? $row['choix'] : (is_string($row['choix']) ? json_decode($row['choix'], true) : []);
            if (is_array($choixData)) {
                foreach ($choixData as $choice) {
                    $value = '';
                    if (is_array($choice) && isset($choice['value'])) {
                        $value = $choice['value'];
                    } elseif (is_object($choice) && isset($choice->value)) {
                        $value = $choice->value;
                    } elseif (is_string($choice)) {
                        $value = $choice;
                    }
                    
                    if ($value === 'ceremonie') $hasCeremonie = true;
                    if ($value === 'vinHonneur') $hasVinHonneur = true;
                    if ($value === 'repas') $hasRepas = true;
                }
            }
        }
        
        // Convertir confirmation en statut lisible
        $confirmationLabel = 'En attente';
        if ($confirmation === 1 || $confirmation === '1') {
            $confirmationLabel = 'Oui';
        } elseif ($confirmation === 0 || $confirmation === '0') {
            $confirmationLabel = 'Non';
        }
        
        // Convertir nuit en booléen
        $nuitBool = ($nuit === true || $nuit === 1 || $nuit === '1');
        
        $formatted[] = [
            'famille' => $famille,
            'email' => $email,
            'confirmation' => $confirmationLabel,
            'confirmation_value' => $confirmation,
            'nb_personnes' => $nbPersonnes,
            'invites_list' => implode(', ', $invitesList),
            'ceremonie' => $hasCeremonie ? '✓' : '–',
            'vin_honneur' => $hasVinHonneur ? '✓' : '–',
            'repas' => $hasRepas ? '✓' : '–',
            'nuit' => $nuitBool ? 'Oui' : 'Non',
            'date_confirmation' => $dateConfirmation,
        ];
        
        // Mettre à jour les totaux
        $totals['total_familles']++;
        
        if ($confirmation === 1 || $confirmation === '1') {
            $totals['confirmations_oui']++;
            $totals['total_personnes'] += $nbPersonnes;
        } elseif ($confirmation === 0 || $confirmation === '0') {
            $totals['confirmations_non']++;
        } else {
            $totals['confirmations_attente']++;
        }
        
        if ($hasCeremonie) $totals['ceremonie_oui'] += $nbPersonnes;
        if ($hasVinHonneur) $totals['vin_honneur_oui'] += $nbPersonnes;
        if ($hasRepas) $totals['repas_oui'] += $nbPersonnes;
        if ($nuitBool) $totals['nuit_oui'] += $nbPersonnes;
    }
    
    // Calculer le taux de réponse
    $reponses = $totals['confirmations_oui'] + $totals['confirmations_non'];
    $tauxReponse = $totals['total_familles'] > 0 ? round(($reponses / $totals['total_familles']) * 100, 1) : 0;
    $totals['taux_reponse'] = $tauxReponse . '%';
    
    error_log('[Dashboard] Données récupérées - ' . $totals['total_familles'] . ' familles', 3, __DIR__ . '/../debug.log');
    
    echo json_encode([
        'ok' => true,
        'data' => $formatted,
        'totals' => $totals,
    ]);
    
} catch (Exception $e) {
    error_log('[Dashboard] Erreur API : ' . $e->getMessage(), 3, __DIR__ . '/../debug.log');
    
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Erreur lors de la récupération des données',
        'error' => $e->getMessage(),
    ]);
}
?>
