<?php
/**
 * Endpoint d'authentification pour l'accès au dashboard
 * POST /php/auth-dashboard.php
 * Body: { "password": "mot_de_passe" }
 */

session_start();
header('Content-Type: application/json');

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

$dashboardPassword = $_ENV['DASHBOARD_PASSWORD'] ?? getenv('DASHBOARD_PASSWORD');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$providedPassword = $input['password'] ?? '';

// Vérifier le mot de passe
if ($dashboardPassword && password_verify($providedPassword, $dashboardPassword)) {
    $_SESSION['dashboard_authenticated'] = true;
    $_SESSION['dashboard_auth_time'] = time();
    
    error_log('[Dashboard] Authentification réussie', 3, __DIR__ . '/../debug.log');
    
    echo json_encode(['ok' => true, 'message' => 'Authentification réussie']);
} else {
    // Mot de passe incorrect ou non configuré
    error_log('[Dashboard] Tentative d\'authentification échouée', 3, __DIR__ . '/../debug.log');
    
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Mot de passe incorrect']);
}
?>
