<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
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

// Charger les variables d'environnement
$dotenvPath = __DIR__ . '/../.env';
if (file_exists($dotenvPath)) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Récupérer les paramètres POST
$familyCode = trim((string) ($_POST['family_code'] ?? ''));
$attendance = trim((string) ($_POST['attendance'] ?? ''));
$meal = trim((string) ($_POST['meal'] ?? ''));
$comments = trim((string) ($_POST['comments'] ?? ''));
$inviteEmail = trim((string) ($_POST['invite_email'] ?? ''));
$inviteName = trim((string) ($_POST['invite_name'] ?? ''));

if ($familyCode === '' || $attendance === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Données incomplètes']);
    exit;
}

// Récupérer la config Baserow
$baserowApiUrl = getenv('BASEROW_API_URL');
$baserowToken = getenv('BASEROW_TOKEN');
$baserowTableId = getenv('BASEROW_TABLE_ID');

if (!$baserowApiUrl || !$baserowToken || !$baserowTableId) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Configuration Baserow incomplète']);
    exit;
}

try {
    $client = new Client();

    // Préparer les données à mettre à jour
    $updateData = [
        'statut_rsvp' => $attendance === 'yes' ? 'Confirmé' : 'Refusé',
        'date_reponse' => date('Y-m-d H:i:s'),
    ];

    if ($attendance === 'yes' && $meal) {
        $updateData['preference_repas'] = $meal;
    }

    if ($comments) {
        $updateData['commentaires'] = $comments;
    }

    // Chercher la ligne correspondant au code famille
    $searchResponse = $client->get("{$baserowApiUrl}/database/tables/{$baserowTableId}/rows/", [
        'headers' => [
            'Authorization' => "Token {$baserowToken}",
            'Content-Type' => 'application/json',
        ],
        'query' => [
            'search' => $inviteName,
            'search_mode' => 'simple',
        ],
    ]);

    $searchData = json_decode($searchResponse->getBody()->getContents(), true);

    if (!isset($searchData['results']) || empty($searchData['results'])) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Ligne Baserow non trouvée']);
        exit;
    }

    $rowId = $searchData['results'][0]['id'];

    // Mettre à jour la ligne dans Baserow
    $updateResponse = $client->patch(
        "{$baserowApiUrl}/database/tables/{$baserowTableId}/rows/{$rowId}/",
        [
            'headers' => [
                'Authorization' => "Token {$baserowToken}",
                'Content-Type' => 'application/json',
            ],
            'json' => $updateData,
        ]
    );

    if ($updateResponse->getStatusCode() === 200) {
        echo json_encode([
            'ok' => true,
            'message' => 'Votre réponse a été enregistrée avec succès',
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'message' => 'Erreur lors de la mise à jour de la base',
        ]);
    }
} catch (GuzzleException $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Erreur lors de l\'enregistrement',
        'error' => $exception->getMessage(),
    ]);
}
