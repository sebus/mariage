<?php
// Récupérer le code depuis l'URL
$code = isset($_GET['code']) ? trim((string) $_GET['code']) : '';
$familyData = null;

if ($code) {
    // Charger l'autoloader Composer
    $autoloadPath = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoloadPath)) {
        require $autoloadPath;

        try {
            // Charger les variables d'environnement
            $dotenvPath = __DIR__ . '/.env';
            if (file_exists($dotenvPath)) {
                $dotenv = Dotenv\Dotenv::createMutable(__DIR__);
                $dotenv->load();
            }

            // Récupérer la config Baserow
            $baserowApiUrl = $_ENV['BASEROW_API_URL'] ?? getenv('BASEROW_API_URL');
            $baserowToken = $_ENV['BASEROW_TOKEN'] ?? getenv('BASEROW_TOKEN');
            $baserowTableId = $_ENV['BASEROW_TABLE_ID'] ?? getenv('BASEROW_TABLE_ID');

            if ($baserowApiUrl && $baserowToken && $baserowTableId) {
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

                // Chercher la ligne avec le bon code
                if (isset($data['results'])) {
                    foreach ($data['results'] as $result) {
                        if (isset($result['code']) && $result['code'] === $code) {
                            $familyData = $result;
                            break;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Silencieusement échouer et rediriger
            header('Location: index.php', true, 302);
            exit;
        }
    }
}

// Si le code est manquant ou la famille non trouvée, rediriger vers index.php
if (!$code || !$familyData) {
    header('Location: index.php', true, 302);
    exit;
}
?>
<!doctype html>

<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="One page mariage - HTML5, Pico CSS, PHPMailer">
    <title>Mariage - One Page</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <main id="page">

        <section class="section hero" id="accueil">
            <div class="container">
                <div class="row">
                    <div class="col">
                        <h1>Béatrice & Sébastien</h1>
                        <p>Samedi 24 avril 2027</p>
                        <h3> Bonjour <?php echo $familyData ? htmlspecialchars($familyData['famille'] ?? 'à vous') : 'à vous'; ?> ! </h3>
                        <p>Bérénice, Vadim et Milan sont heureux<br>de vous inviter au mariage de leurs parents :</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <nav class="site-nav" aria-label="Navigation principale">
                            <ul class="nav nav-inline">
                                <!-- <li><a href="#programme">Programme de la journée </a></li> -->
                                <li><a href="#rsvp">Confirmez votre venue !</a></li>
                                <!-- <li><a href="#menu">Menu du samedi soir</a></li> -->
                                <li><a href="#infos">Informations pratiques, adresses...</a></li>
                                <li><a href="#contact">Contactez-nous !</a></li>
                                <!-- <li><a href="#accueil">Photos</a></li> -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </section>

        <!-- <section class="section" id="programme">
            <div class="container">
                <div class="row">
                    <div class="col">
                        <h2>Programme de la journée<br>samedi 24 avril 2027</h2>

                        <h3>Cérémonie</h3>
                        <p>15h à la salle des mariage à Montmélian (<a href="">voir le plan</a>)</p>
                        <p>16h préparation des voitures ! </p>
                        <h3>Vin d'honneur</h3>
                        <p>17h accueil au Château du Montalieu (<a href="">voir le plan</a>)</p>
                        <p>18h - 20h Vin d'honneur</p>
                        <h3>Repas</h3>
                        <p>20h30 Entrée des mariés</p>
                        <p>Repas</p>
                        <p>...</p>
                        <h3>Soirée !</h3>

                        <h2>Dimance 25 avril 2027</h2>

                        <h3>Brunch</h3>
                        <p>10h - 17h Brunch maison, toujours au Château</p>

                    </div>
                </div>
        </section> -->

        <section class="section rsvp" id="rsvp">
            <div class="container">
                <div class="row">
                    <div class="col">
                        <h2>Confirmez votre venue !</h2>
                        <p>Réponse souhaitée avant le 15 janvier 2027</p>
                    </div>
                    <div class="row">
                        <div class="col">
                            <!-- Formulaire initial de confirmation (Oui/Non) -->
                            <?php 
                                // Déterminer l'état de confirmation (INT: vide/null = pas de choix, 0 = Non, 1 = Oui)
                                $confirmation = isset($familyData['confirmation']) ? $familyData['confirmation'] : null;
                                
                                // Convertir en int si c'est une valeur numérique
                                if ($confirmation !== null && $confirmation !== '') {
                                    $confirmation = (int)$confirmation;
                                } else {
                                    $confirmation = null;
                                }
                            ?>
                            
                            <?php if ($confirmation === null): ?>
                            <form id="confirmation-form" name="confirmation">
                                <input type="hidden" name="code" value="<?php echo $code ? htmlspecialchars($code) : ''; ?>">
                                <fieldset>
                                    <label for="presence"><strong>Viendrez vous à notre mariage ?</strong>
                                        <div class="split">
                                            <button type="button" id="btn-yes" class="btn-confirm" data-confirm="true">Oui</button>
                                            <button type="button" id="btn-no" class="btn-confirm" data-confirm="false">Non</button>
                                        </div>
                                    </label>
                                </fieldset>
                            </form>

                            <!-- Formulaire détaillé (affiche seulement si confirmation = 1) -->
                            <?php elseif ($confirmation === 1): ?>
                            <form id="details-form" name="confirmationDetail">
                                <input type="hidden" name="code" value="<?php echo $code ? htmlspecialchars($code) : ''; ?>">
                                <fieldset>
                                    <label for="noms"><strong>Super ! Qui vient ?</strong>
                                        <input name="noms" type="text" value="<?php echo $familyData ? htmlspecialchars($familyData['famille'] ?? '') : ''; ?>" required>
                                    </label>
                                </fieldset>

                                <fieldset>
                                    <label for="nbre_personne"><strong>Pour être certain, cela fait combien de personnes ?</strong>
                                        <input name="nbre_personne" type="number" value="<?php echo $familyData ? htmlspecialchars($familyData['adulte'] ?? '') : ''; ?>" required>
                                    </label>
                                </fieldset>

                                <fieldset>
                                    <label for="email"><strong>Un email pour la famille</strong>
                                        <input type="email" name="email" value="<?php echo $familyData ? htmlspecialchars($familyData['email'] ?? '') : ''; ?>" placeholder="Votre adresse email" autocomplete="email" required>
                                        Saisissez le une seconde fois :
                                        <input type="email" name="email_confirm" placeholder="Votre adresse email" autocomplete="email" required>
                                    </label>
                                </fieldset>

                                <fieldset>
                                    <legend><strong>Confirmez vos choix</strong></legend>
                                    <label for="select_ceremonie">
                                        <input name="select_ceremonie" type="checkbox" role="switch" checked />
                                        Cérémonie
                                    </label>
                                    <label for="select_vh">
                                        <input name="select_vh" type="checkbox" role="switch" /> Vin d'honneur
                                    </label>
                                    <label for="select_repas">
                                        <input name="select_repas" type="checkbox" role="switch" /> Repas et soirée
                                    </label>
                                </fieldset>

                                <fieldset>
                                    <label for="choix_nuit"><strong>Pensez-vous rester sur place pour la nuit</strong> ?
                                        Si oui, nous reviendrons vers vous pour des propositions de logement.
                                        <select name="choix_nuit" required>
                                            <option selected disabled value="">Votre choix pour la nuit</option>
                                            <option>Oui j'aimerais rester</option>
                                            <option>Non je rentrerai apès la soirée</option>
                                        </select>
                                    </label>
                                </fieldset>

                                <fieldset>
                                    <label for="consignes"><strong>Avez-vous des impératifs pour le repas, allergie, menu végétarien, etc ?</strong>
                                        <textarea name="consignes"></textarea>
                                    </label>
                                </fieldset>

                                <fieldset>
                                    <label for="dance"><strong>Et enfin, quel morceau vous fait danser sur la piste ?</strong>
                                        <input type="text" name="dance">
                                    </label>
                                </fieldset>

                                <input type="submit" value="C'est tout bon ? Envoyez !" />
                                <p>Astuce : vous pourrez modifier vos choix jusqu'au ...</p>
                            </form>

                            <!-- Message si confirmation = false -->
                            <?php else: ?>
                            <input type="hidden" name="code" value="<?php echo $code ? htmlspecialchars($code) : ''; ?>">
                            <fieldset>
                                <label for="presence_non"><strong>Snif :(</strong> Vous allez nous manquer...</label>
                                <p style="margin-top: 1rem;">
                                    <a href="#" id="reset-choice" style="color: var(--form-element-invalid-border-color);">
                                        Je me suis trompé, je voudrais changer mon choix
                                    </a>
                                </p>
                            </fieldset>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- <section class="section" id="menu">
            <div class="container">
                <div class="row">
                    <div class="col">
                        <h2>Menu samedi soir</h2>
                    </div>
                </div>
        </section> -->

        <section class="section" id="infos">
            <div class="container">
                <div class="row">
                    <div class="col">
                        <h2>Informations pratiques</h2>
                    </div>
                </div>
        </section>

        <section class="section" id="contact">
            <div class="container">
                <div class="row">
                    <div class="col">
                        <h2>Contactez-nous !</h2>
                    </div>
                </div>
        </section>

    </main>

    <script src="assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Gérer les clics sur les boutons Oui/Non
            const confirmButtons = document.querySelectorAll('.btn-confirm');
            confirmButtons.forEach(button => {
                button.addEventListener('click', async function (e) {
                    e.preventDefault();
                    
                    const confirmation = this.dataset.confirm === 'true';
                    const code = document.querySelector('input[name="code"]').value;
                    
                    // Préparer les données
                    const formData = new FormData();
                    formData.append('code', code);
                    formData.append('confirmation', confirmation ? '1' : '0');
                    
                    try {
                        const response = await fetch('php/update-rsvp.php', {
                            method: 'POST',
                            body: formData,
                        });
                        
                        const result = await response.json();
                        
                        if (result.ok) {
                            // Recharger la page pour afficher le formulaire approprié
                            window.location.reload();
                        } else {
                            alert('Erreur: ' + result.message);
                        }
                    } catch (error) {
                        alert('Erreur lors de l\'envoi: ' + error.message);
                    }
                });
            });

            // Gérer le lien "Je me suis trompé"
            const resetLink = document.getElementById('reset-choice');
            if (resetLink) {
                resetLink.addEventListener('click', async function (e) {
                    e.preventDefault();
                    
                    const code = document.querySelector('input[name="code"]') ? 
                                 document.querySelector('input[name="code"]').value : '';
                    
                    if (!code) {
                        alert('Erreur: code manquant');
                        return;
                    }
                    
                    // Préparer les données
                    const formData = new FormData();
                    formData.append('code', code);
                    
                    try {
                        const response = await fetch('php/reset-rsvp.php', {
                            method: 'POST',
                            body: formData,
                        });
                        
                        const result = await response.json();
                        
                        if (result.ok) {
                            // Recharger la page pour afficher à nouveau les boutons Oui/Non
                            window.location.reload();
                        } else {
                            alert('Erreur: ' + result.message);
                        }
                    } catch (error) {
                        alert('Erreur lors de l\'envoi: ' + error.message);
                    }
                });
            }
        });
    </script>
</body>

</html>