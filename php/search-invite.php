<?php

declare(strict_types=1);

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

/**
 * Fonction pour normaliser une chaîne
 * - Enlève les accents
 * - Réduit les espaces multiples
 */
function normalizeString($str) {
    // Enlever les accents
    $str = preg_replace("~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i", '$1', htmlentities($str, ENT_QUOTES, 'UTF-8'));
    // Alternative : utiliser iconv si disponible
    if (function_exists('iconv')) {
        $str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
    }
    // Réduire les espaces multiples
    $str = preg_replace('/\s+/', ' ', $str);
    return trim($str);
}

try {
    // Charger les variables d'environnement
    $dotenvPath = __DIR__ . '/../.env';
    if (file_exists($dotenvPath)) {
        $dotenv = Dotenv\Dotenv::createMutable(__DIR__ . '/..');
        $dotenv->load();
    }

    // Récupérer les paramètres POST
    $searchName = trim((string) ($_POST['invite'] ?? ''));

    if ($searchName === '') {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Veuillez saisir un prénom et un nom']);
        exit;
    }

    // Normaliser la recherche : minuscules, enlever accents, espaces multiples
    $searchName = strtolower($searchName);
    $searchName = normalizeString($searchName);

    // Récupérer la config Baserow (via $_ENV ou superglobals)
    $baserowApiUrl = $_ENV['BASEROW_API_URL'] ?? getenv('BASEROW_API_URL');
    $baserowToken = $_ENV['BASEROW_TOKEN'] ?? getenv('BASEROW_TOKEN');
    $baserowTableId = $_ENV['BASEROW_TABLE_ID'] ?? getenv('BASEROW_TABLE_ID');

    if (!$baserowApiUrl || !$baserowToken || !$baserowTableId) {
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'message' => 'Configuration Baserow incomplète',
            'debug' => [
                'BASEROW_API_URL' => $baserowApiUrl ? 'OK' : 'MANQUANT',
                'BASEROW_TOKEN' => $baserowToken ? 'OK' : 'MANQUANT',
                'BASEROW_TABLE_ID' => $baserowTableId ? 'OK' : 'MANQUANT',
            ]
        ]);
        exit;
    }

    $client = new GuzzleHttp\Client();

    // Récupérer les résultats avec les deux ordres possibles de mots
    $allResults = [];

    // Premier appel : avec l'ordre fourni
    $response = $client->get("{$baserowApiUrl}/database/rows/table/{$baserowTableId}/", [
        'headers' => [
            'Authorization' => "Token {$baserowToken}",
            'Content-Type' => 'application/json',
        ],
        'query' => [
            'user_field_names' => 'true',
            'search' => $searchName,
        ],
    ]);

    $responseBody = $response->getBody()->getContents();
    $data = json_decode($responseBody, true);

    if (!empty($data['results'])) {
        $allResults = array_merge($allResults, $data['results']);
    }

    // Deuxième appel : essayer avec l'ordre inversé des mots
    $words = preg_split('/\s+/', $searchName, -1, PREG_SPLIT_NO_EMPTY);
    if (count($words) >= 2) {
        $reversedSearchName = implode(' ', array_reverse($words));
        
        $response = $client->get("{$baserowApiUrl}/database/rows/table/{$baserowTableId}/", [
            'headers' => [
                'Authorization' => "Token {$baserowToken}",
                'Content-Type' => 'application/json',
            ],
            'query' => [
                'user_field_names' => 'true',
                'search' => $reversedSearchName,
            ],
        ]);

        $responseBody = $response->getBody()->getContents();
        $data = json_decode($responseBody, true);

        if (!empty($data['results'])) {
            $allResults = array_merge($allResults, $data['results']);
        }
    }

    // Vérifier qu'on a au moins un résultat
    if (empty($allResults)) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Aucun invité trouvé']);
        exit;
    }

    // Chercher le meilleur match parmi les résultats
    // Stratégie : chercher d'abord sur fullNameStarsky, puis sur fullNameHutch
    // Tester aussi avec les mots inversés si plusieurs mots dans la recherche
    $bestMatch = null;
    $bestSimilarity = 0;
    $matchedField = null;

    // Préparer les variantes de recherche
    $searchVariants = [$searchName];
    $words = preg_split('/\s+/', $searchName, -1, PREG_SPLIT_NO_EMPTY);
    if (count($words) >= 2) {
        $searchVariants[] = implode(' ', array_reverse($words));
    }

    foreach ($allResults as $result) {
        // Chercher sur fullNameStarsky d'abord
        $nomStarsky = isset($result['fullNameStarsky']) ? trim((string) $result['fullNameStarsky']) : '';
        if ($nomStarsky !== '') {
            $nomNormalize = strtolower($nomStarsky);
            $nomNormalize = normalizeString($nomNormalize);

            // Tester contre toutes les variantes
            foreach ($searchVariants as $variant) {
                // Vérifier correspondance exacte
                if ($nomNormalize === $variant) {
                    $bestMatch = $result;
                    $bestSimilarity = 100;
                    $matchedField = 'fullNameStarsky';
                    break 2;
                }

                // Calculer la similarité
                similar_text($variant, $nomNormalize, $similarity);
                if ($similarity > $bestSimilarity) {
                    $bestSimilarity = $similarity;
                    $bestMatch = $result;
                    $matchedField = 'fullNameStarsky';
                }
            }
        }

        // Chercher sur fullNameHutch si pas de bon match sur Starsky
        $nomHutch = isset($result['fullNameHutch']) ? trim((string) $result['fullNameHutch']) : '';
        if ($nomHutch !== '' && $bestSimilarity < 80) {
            $nomNormalize = strtolower($nomHutch);
            $nomNormalize = normalizeString($nomNormalize);

            // Tester contre toutes les variantes
            foreach ($searchVariants as $variant) {
                // Vérifier correspondance exacte
                if ($nomNormalize === $variant) {
                    $bestMatch = $result;
                    $bestSimilarity = 100;
                    $matchedField = 'fullNameHutch';
                    break 2;
                }

                // Calculer la similarité
                similar_text($variant, $nomNormalize, $similarity);
                if ($similarity > $bestSimilarity) {
                    $bestSimilarity = $similarity;
                    $bestMatch = $result;
                    $matchedField = 'fullNameHutch';
                }
            }
        }
    }

    // Vérifier qu'on a un match de qualité (au moins 50% de similarité)
    if (!$bestMatch || $bestSimilarity < 50) {
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'message' => 'Invité non trouvé. Vérifiez l\'orthographe de votre nom.'
        ]);
        exit;
    }

    // Retourner les données du meilleur match
    $familyCode = $bestMatch['code'] ?? null;
    $email = $bestMatch['email'] ?? null;
    $nom = $bestMatch[$matchedField] ?? ($bestMatch['nom'] ?? $searchName);

    if (!$familyCode) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Code famille manquant dans la base']);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'message' => 'Invité trouvé',
        'data' => [
            'nom' => $nom,
            'email' => $email,
            'code' => $familyCode,
        ],
    ]);
    exit;

} catch (GuzzleHttp\Exception\GuzzleException $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Erreur API Baserow',
        'error' => $exception->getMessage(),
    ]);
} catch (Exception $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Erreur serveur',
        'error' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString(),
    ]);
}
