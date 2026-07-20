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
                        <form id="search-form">
                            <fieldset>
                                <label for="invite"><strong>Votre prénom et votre nom</strong>
                                    <input name="invite" id="invite" type="text" value="" placeholder="ex: Marie Dupont" required>
                                </label>
                            </fieldset>
                            <input type="submit" value="Trouvez moi !" />
                        </form>
                        <div id="search-error" popover>
                            <strong>✗ Invité non trouvé</strong>
                            <p id="error-message"></p>
                        </div>
                        <div id="search-loading" popover>
                            <p>Recherche en cours...</p>
                        </div>
                    </div>
                </div>
                
            </div>
        </section>

    <script src="assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchForm = document.getElementById('search-form');
            const searchError = document.getElementById('search-error');
            const searchLoading = document.getElementById('search-loading');
            const errorMessage = document.getElementById('error-message');

            searchForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                
                const inviteName = document.getElementById('invite').value.trim();
                
                if (!inviteName) {
                    errorMessage.textContent = 'Veuillez saisir un prénom et un nom';
                    searchError.showPopover();
                    return;
                }

                // Vérifier qu'on a au moins 2 mots (prénom et nom)
                const words = inviteName.split(/\s+/).filter(word => word.length > 0);
                if (words.length < 2) {
                    errorMessage.textContent = 'Veuillez saisir votre prénom et votre nom (ex: Marie Dupont)';
                    searchError.showPopover();
                    return;
                }

                searchLoading.showPopover();
                searchError.hidePopover();

                try {
                    const formData = new FormData();
                    formData.append('invite', inviteName);

                    const response = await fetch('php/search-invite.php', {
                        method: 'POST',
                        body: formData,
                    });

                    const result = await response.json();

                    if (result.ok && result.data) {
                        // Rediriger vers la page RSVP avec seulement le code
                        window.location.href = `rsvp.php?code=${encodeURIComponent(result.data.code)}`;
                    } else {
                        searchLoading.hidePopover();
                        errorMessage.textContent = result.message || 'Invité non trouvé';
                        searchError.showPopover();
                    }
                } catch (error) {
                    searchLoading.hidePopover();
                    errorMessage.textContent = error.message;
                    searchError.showPopover();
                }
            });
        });
    </script>
</body>

</html>