<?php
// Démarrer la session
session_start();

// Vérifier l'authentification
$isAuthenticated = isset($_SESSION['dashboard_authenticated']) && $_SESSION['dashboard_authenticated'] === true;

// Vérifier le timeout de session
if ($isAuthenticated && isset($_SESSION['dashboard_auth_time'])) {
    $timeout = 30 * 60; // 30 minutes
    if (time() - $_SESSION['dashboard_auth_time'] > $timeout) {
        unset($_SESSION['dashboard_authenticated']);
        $isAuthenticated = false;
    } else {
        // Réinitialiser le timer
        $_SESSION['dashboard_auth_time'] = time();
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Dashboard RSVP - Page récapitulative des réponses">
    <title>Dashboard RSVP - Tableau récapitulatif</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">

</head>
<body>
    <div class="dashboard-container">
        
        <?php if (!$isAuthenticated): ?>
            <!-- Formulaire d'authentification -->
            <div class="auth-form">
                <h1>🔐 Accès Dashboard RSVP</h1>
                <p>Veuillez entrer le mot de passe pour accéder au tableau récapitulatif.</p>
                
                <form id="auth-form">
                    <fieldset>
                        <label for="password">
                            <strong>Mot de passe</strong>
                            <input type="password" id="password" name="password" placeholder="Entrez le mot de passe" required autofocus>
                        </label>
                    </fieldset>
                    <button type="submit" id="auth-btn">Se connecter</button>
                </form>
                
                <div id="auth-error" style="display: none; margin-top: 1rem;"></div>
            </div>
            
            <script>
                document.getElementById('auth-form').addEventListener('submit', async function(e) {
                    e.preventDefault();
                    const password = document.getElementById('password').value;
                    const btn = document.getElementById('auth-btn');
                    const errorDiv = document.getElementById('auth-error');
                    
                    btn.disabled = true;
                    btn.textContent = 'Connexion en cours...';
                    errorDiv.style.display = 'none';
                    
                    try {
                        const response = await fetch('php/auth-dashboard.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ password }),
                        });
                        
                        const result = await response.json();
                        
                        if (result.ok) {
                            // Rechargement de la page
                            window.location.reload();
                        } else {
                            btn.disabled = false;
                            btn.textContent = 'Se connecter';
                            errorDiv.style.display = 'block';
                            errorDiv.className = 'error';
                            errorDiv.textContent = result.message || 'Erreur d\'authentification';
                            document.getElementById('password').value = '';
                            document.getElementById('password').focus();
                        }
                    } catch (error) {
                        btn.disabled = false;
                        btn.textContent = 'Se connecter';
                        errorDiv.style.display = 'block';
                        errorDiv.className = 'error';
                        errorDiv.textContent = 'Erreur réseau : ' + error.message;
                    }
                });
            </script>
        
        <?php else: ?>
            <!-- Dashboard authentifié -->
            <div class="header-section">
                <h1>Tableau récapitulatif des RSVP</h1>
                <!-- <button class="logout-btn" onclick="logout()">Déconnexion</button> -->
            </div>

            <div class="search-box">
                        <input type="text" id="search-input" placeholder="Rechercher par nom de famille...">
                    </div>
            
            <div class="table-container">
                <div id="loading" class="loading">
                    ⏳ Chargement des données...
                </div>
                
                <div id="error" style="display: none; margin: 1rem;"></div>

                <div class="totals-section">
                    <h3>Résumé</h3>
                    <div class="totals-grid">
                        <div class="total-box">
                            <strong id="total-familles">0</strong>
                            <span>Familles</span>
                        </div>
                        <div class="total-box">
                            <strong id="total-confirmations-oui">0</strong>
                            <span>Confirmations (Oui)</span>
                        </div>
                        <div class="total-box">
                            <strong id="total-confirmations-non">0</strong>
                            <span>Confirmations (Non)</span>
                        </div>
                        <div class="total-box">
                            <strong id="total-confirmations-attente">0</strong>
                            <span>En attente</span>
                        </div>
                        <div class="total-box">
                            <strong id="total-taux-reponse">0%</strong>
                            <span>Taux de réponse</span>
                        </div>
                        <div class="total-box">
                            <strong id="total-personnes">0</strong>
                            <span>Personnes confirmées</span>
                        </div>
                        <div class="total-box">
                            <strong id="total-ceremonie">0</strong>
                            <span>Cérémonie</span>
                        </div>
                        <div class="total-box">
                            <strong id="total-vin-honneur">0</strong>
                            <span>Vin d'honneur</span>
                        </div>
                        <div class="total-box">
                            <strong id="total-repas">0</strong>
                            <span>Repas</span>
                        </div>
                        <div class="total-box">
                            <strong id="total-nuit">0</strong>
                            <span>Demandes de nuit</span>
                        </div>
                    </div>
                </div>
                
                <div id="table-wrapper" style="display: none; overflow-x: auto;">
                    <h3>Tableau</h3>
                    

                    <table>
                        <thead>
                            <tr>
                                <th>Famille</th>
                                <th>Email</th>
                                <th>Confirmation</th>
                                <th>Personnes</th>
                                <th>Cérémonie</th>
                                <th>Vin d'honneur</th>
                                <th>Repas</th>
                                <th>Nuit</th>
                                <th>Date réponse</th>
                            </tr>
                        </thead>
                        <tbody id="table-body">
                        </tbody>
                    </table>
                </div>
                
                <div id="no-results" style="display: none;" class="no-results">
                    <p>Aucun résultat ne correspond à votre recherche.</p>
                </div>
                
                
            </div>
            
            <script src="assets/js/dashboard.js"></script>
            
            <script>
                function logout() {
                    if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
                        fetch('php/auth-dashboard.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ logout: true }),
                        }).then(() => {
                            window.location.reload();
                        });
                        // Alternative si logout n'est pas géré
                        setTimeout(() => {
                            window.location.href = 'dashboard.php';
                        }, 500);
                    }
                }
            </script>
        
        <?php endif; ?>
    
    </div>
</body>
</html>
