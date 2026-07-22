<?php

declare(strict_types=1);

session_start();

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

try {
    // Charger les variables d'environnement
    $dotenvPath = __DIR__ . '/../.env';
    if (file_exists($dotenvPath)) {
        $dotenv = Dotenv\Dotenv::createMutable(__DIR__ . '/..');
        $dotenv->load();
    }

    // Récupérer le code depuis la session et la confirmation depuis POST
    $code = isset($_SESSION['family_code']) ? trim((string) $_SESSION['family_code']) : '';
    $confirmation = isset($_POST['confirmation']) ? (int)$_POST['confirmation'] : null;

    if ($code === '' || $confirmation === null) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Paramètres manquants']);
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
        echo json_encode(['ok' => false, 'message' => 'Famille non trouvée']);
        exit;
    }

    // Préparer la date au format ISO (YYYY-MM-DD)
    $dateConfirmation = date('Y-m-d');

    // Mettre à jour la ligne Baserow (avec user_field_names=true pour utiliser les noms personnalisés)
    $updateResponse = $client->patch(
        "{$baserowApiUrl}/database/rows/table/{$baserowTableId}/{$rowId}/?user_field_names=true",
        [
            'headers' => [
                'Authorization' => "Token {$baserowToken}",
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'confirmation' => $confirmation,
                'dateConfirmation' => $dateConfirmation,
            ],
        ]
    );

    $updateResponseBody = $updateResponse->getBody()->getContents();
    $updateData = json_decode($updateResponseBody, true);

    echo json_encode([
        'ok' => true,
        'message' => 'RSVP enregistré',
        'data' => $updateData,
    ]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
    exit;
}
