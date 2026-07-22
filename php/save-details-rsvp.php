<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=utf-8');

// Log pour débugage
$logFile = __DIR__ . '/../debug.log';
file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Requête reçue\n", FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    file_put_contents($logFile, "- Erreur: méthode non POST (" . $_SERVER['REQUEST_METHOD'] . ")\n", FILE_APPEND);
    echo json_encode(['ok' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Charger l'autoloader Composer
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Dépendances manquantes']);
    exit;
}

require $autoloadPath;

try {
    // Charger les variables d'environnement
    $dotenvPath = __DIR__ . '/../.env';
    if (file_exists($dotenvPath)) {
        $dotenv = Dotenv\Dotenv::createMutable(__DIR__ . '/..');
        $dotenv->load();
    }

    // Récupérer le code depuis la session
    $code = isset($_SESSION['family_code']) ? trim((string) $_SESSION['family_code']) : '';
    $noms = trim((string) ($_POST['noms'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $invites = isset($_POST['invites']) ? $_POST['invites'] : [];
    $selectCeremonie = isset($_POST['select_ceremonie']) ? 1 : 0;
    $selectVh = isset($_POST['select_vh']) ? 1 : 0;
    $selectRepas = isset($_POST['select_repas']) ? 1 : 0;
    $choixNuit = trim((string) ($_POST['choix_nuit'] ?? ''));
    $consignes = trim((string) ($_POST['consignes'] ?? ''));
    $dance = trim((string) ($_POST['dance'] ?? ''));
    $commentaire = trim((string) ($_POST['commentaire'] ?? ''));

    file_put_contents($logFile, "- Code (session): $code\n- Email: $email\n- Choix nuit: $choixNuit\n- Invites: " . json_encode($invites) . "\n", FILE_APPEND);

    if ($code === '' || $email === '') {
        http_response_code(422);
        file_put_contents($logFile, "- Erreur: code ou email vide\n", FILE_APPEND);
        echo json_encode(['ok' => false, 'message' => 'Email et code sont obligatoires']);
        exit;
    }

    // Récupérer la config Baserow
    $baserowApiUrl = $_ENV['BASEROW_API_URL'] ?? getenv('BASEROW_API_URL');
    $baserowToken = $_ENV['BASEROW_TOKEN'] ?? getenv('BASEROW_TOKEN');
    $baserowTableId = $_ENV['BASEROW_TABLE_ID'] ?? getenv('BASEROW_TABLE_ID');

    if (!$baserowApiUrl || !$baserowToken || !$baserowTableId) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => 'Configuration Baserow incomplète']);
        exit;
    }

    $client = new GuzzleHttp\Client();

    // Rechercher la ligne avec ce code
    $response = $client->get("{$baserowApiUrl}/database/rows/table/{$baserowTableId}/", [
        'headers' => [
            'Authorization' => "Token {$baserowToken}",
            'Content-Type' => 'application/json',
        ],
        'query' => [
            'user_field_names' => 'true',
            'search' => $code,
        ],
    ]);

    $responseBody = $response->getBody()->getContents();
    $data = json_decode($responseBody, true);

    if (!isset($data['results']) || empty($data['results'])) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Famille non trouvée']);
        exit;
    }

    // Chercher la ligne avec le bon code
    $rowId = null;
    foreach ($data['results'] as $result) {
        if (isset($result['id']) && isset($result['code']) && $result['code'] === $code) {
            $rowId = $result['id'];
            break;
        }
    }

    if (!$rowId) {
        http_response_code(404);
        file_put_contents($logFile, "- Erreur: rowId non trouvé pour le code: $code\n", FILE_APPEND);
        echo json_encode(['ok' => false, 'message' => 'Famille non trouvée']);
        exit;
    }

    file_put_contents($logFile, "- RowID trouvé: $rowId\n", FILE_APPEND);

    // Récupérer les invites actuels de la base
    $currentRow = null;
    foreach ($data['results'] as $result) {
        if (isset($result['id']) && $result['id'] === $rowId) {
            $currentRow = $result;
            break;
        }
    }

    // Mettre à jour le statut des invites
    $updatedInvites = [];
    if (isset($currentRow['invites']) && !empty($currentRow['invites'])) {
        $currentInvitesData = json_decode($currentRow['invites'], true);
        if (is_array($currentInvitesData)) {
            foreach ($currentInvitesData as $invite) {
                $prenom = $invite['prenom'] ?? '';
                // Vérifier si ce prénom est coché
                $statut = in_array($prenom, $invites) ? 'confirme' : 'attente';
                $updatedInvites[] = [
                    'prenom' => $prenom,
                    'statut' => $statut,
                ];
            }
        }
    }

    // Préparer la date au format ISO (YYYY-MM-DD)
    $dateConfirmation = date('Y-m-d');

    // Déterminer le choix nuit (true si "Oui", false si "Non")
    $nuitValue = strpos($choixNuit, 'Oui') !== false ? true : false;

    // Construire l'array des choix pour le multiple select
    $choixArray = [];
    if ($selectCeremonie) {
        $choixArray[] = 'ceremonie';
    }
    if ($selectVh) {
        $choixArray[] = 'vinHonneur';
    }
    if ($selectRepas) {
        $choixArray[] = 'repas';
    }

    $updateData = [
        'famille' => $noms,
        'email' => $email,
        'choix' => $choixArray,
        'nuit' => $nuitValue,
        'consignes' => $consignes,
        'musique' => $dance,
        'invites' => json_encode($updatedInvites),
        'dateConfirmation' => $dateConfirmation,
    ];

    file_put_contents($logFile, "- Données envoyées à Baserow: " . json_encode($updateData) . "\n", FILE_APPEND);

    // Mettre à jour la ligne Baserow
    $updateResponse = $client->patch(
        "{$baserowApiUrl}/database/rows/table/{$baserowTableId}/{$rowId}/?user_field_names=true",
        [
            'headers' => [
                'Authorization' => "Token {$baserowToken}",
                'Content-Type' => 'application/json',
            ],
            'json' => $updateData,
        ]
    );

    if ($updateResponse->getStatusCode() !== 200) {
        $errorBody = $updateResponse->getBody()->getContents();
        file_put_contents($logFile, "- Erreur: réponse Baserow non 200 (" . $updateResponse->getStatusCode() . ")\n", FILE_APPEND);
        file_put_contents($logFile, "- Body: " . $errorBody . "\n", FILE_APPEND);
        throw new Exception('Erreur lors de la mise à jour Baserow: ' . $errorBody);
    }

    file_put_contents($logFile, "- ✓ Mise à jour réussie\n", FILE_APPEND);

    // Envoyer la notification email (non-bloquant)
    try {
        file_put_contents($logFile, "- [EMAIL] Début envoi notification\n", FILE_APPEND);
        require_once __DIR__ . '/send-notification.php';
        $notificationData = array_merge($updateData, ['invites' => $updatedInvites, 'commentaire' => $commentaire]);
        sendRsvpNotification($notificationData, $code, $logFile);
        file_put_contents($logFile, "- [EMAIL] Envoi complété\n", FILE_APPEND);
    } catch (Throwable $e) {
        file_put_contents($logFile, "- [EMAIL] ✗ Erreur: " . $e->getMessage() . "\n", FILE_APPEND);
        // On ne bloque pas la réponse si l'email échoue
    }

    echo json_encode([
        'ok' => true,
        'message' => 'Vos réponses ont été enregistrées avec succès !',
    ]);

} catch (Exception $e) {
    file_put_contents($logFile, "- Exception: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Erreur : ' . $e->getMessage(),
    ]);
}
