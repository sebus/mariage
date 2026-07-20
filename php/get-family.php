<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

    // Récupérer le code en paramètre
    $code = trim((string) ($_GET['code'] ?? ''));

    if ($code === '') {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Code manquant']);
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

    // Appel API Baserow avec filtre sur le code
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
    $familyData = null;
    foreach ($data['results'] as $result) {
        if (isset($result['code']) && $result['code'] === $code) {
            $familyData = $result;
            break;
        }
    }

    if (!$familyData) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Famille non trouvée']);
        exit;
    }

    // Retourner les données
    echo json_encode([
        'ok' => true,
        'message' => 'Famille trouvée',
        'data' => $familyData,
    ]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
    exit;
}
