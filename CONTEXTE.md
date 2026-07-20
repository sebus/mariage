# Contexte du projet

## Objectif

Ce projet a pour but de mettre en place un site one-page pour centraliser les réponses de présence au mariage.

L'objectif n'est pas seulement d'afficher des informations pratiques, mais surtout de construire un flux complet de gestion des RSVP : invitation, identification des familles, réponse via formulaire, mise à jour des données, notification et relance.

Le document présent sert à la fois :

- de synthèse fonctionnelle du projet ;
- de feuille de route d'implémentation ;
- de point de reprise pour les prochaines sessions de travail.

La description de la stack technique et des fichiers de base reste dans [README.md](README.md).

## Finalité du site

Le site doit permettre de :

- présenter les informations principales du mariage ;
- envoyer à chaque famille un lien personnalisé ;
- reconnaître la famille grâce à un code transmis dans l'URL ;
- afficher un formulaire adapté au profil des invités ;
- enregistrer ou mettre à jour leur réponse dans Baserow ;
- déclencher des notifications ;
- permettre des relances automatiques des non-répondants.

## Périmètre

### Ce qui existe déjà dans le dépôt

- une base de site one-page dans [index.html](index.html) ;
- un style front mobile first ;
- une logique JavaScript légère pour la navigation dans [assets/js/main.js](assets/js/main.js) ;
- un endpoint PHP générique d'envoi d'email dans [php/send-mail.php](php/send-mail.php) ;
- une configuration SMTP versionnée dans [config/smtp.php](config/smtp.php) ;
- les dépendances PHP nécessaires installées via Composer.

### Ce qui reste à mettre en place

- la lecture du code famille depuis l'URL ;
- la récupération des données invité depuis Baserow ;
- la logique de personnalisation du formulaire ;
- l'enregistrement des réponses RSVP dans Baserow ;
- les notifications liées aux réponses ;
- la routine de relance des familles sans réponse ;
- la définition précise du rôle de N8n dans le workflow.

### Ce qui dépend d'outils externes

- Baserow pour les données invités et le suivi des réponses ;
- N8n pour les automatisations ;
- la messagerie SMTP pour les envois d'emails.

## Résultat cible

À terme, le fonctionnement attendu est le suivant :

1. une liste d'invités est maintenue dans Baserow ;
2. un envoi manuel ou semi-automatisé génère les invitations initiales ;
3. chaque email contient un lien personnalisé avec un code famille ;
4. le site reconnaît la famille et adapte le formulaire ;
5. la réponse est enregistrée dans Baserow ;
6. une notification est envoyée ;
7. un système de relance traite les absences de réponse.

## Flux métier détaillé

### Étape 1 - Préparer les données dans Baserow

Une table Baserow doit contenir au minimum les informations nécessaires pour piloter les invitations et le suivi des réponses.

Champs pressentis :

- email principal ;
- nom de famille ou libellé foyer ;
- code famille unique ;
- catégorie d'invitation ;
- nombre de personnes attendues ou invitées ;
- statut de réponse ;
- date de dernière relance ;
- préférences ou remarques de repas ;
- présence par moment de la journée ;
- besoins d'hébergement ;
- chanson proposée ;
- commentaires libres.

Sortie attendue : une structure stable, exploitable à la fois par le site, les scripts d'envoi et les automatisations.

Point de vigilance : la structure exacte de la table doit être figée avant de coder l'intégration complète.

### Étape 2 - Déclencher l'envoi initial des invitations

Le flux démarre par une action manuelle. Un script ou une automation devra récupérer dans Baserow les données utiles de chaque invité, puis envoyer un email personnalisé.

Données d'entrée :

- email ;
- famille ;
- code ;
- catégorie.

Sortie attendue : chaque foyer reçoit un email unique contenant le bon lien vers le site.

Décision actuelle : l'envoi initial reste déclenché manuellement pour garder le contrôle.

### Étape 3 - Générer le lien personnalisé

Chaque email doit contenir un lien vers le site avec un paramètre de type code famille.

Exemple de logique attendue :

- le lien contient un identifiant de foyer ;
- le site lit ce code à l'ouverture ;
- ce code permet de déterminer quels blocs du formulaire afficher et quelle ligne Baserow mettre à jour.

Sortie attendue : un invité arrive sur une page déjà contextualisée pour son foyer.

### Étape 4 - Identifier la famille sur le site

Le front doit lire le paramètre dans l'URL puis initialiser l'expérience utilisateur à partir de ce code.

Ce point implique :

- récupération du code dans l'URL ;
- validation minimale du format ;
- chargement des données liées à ce code ;
- affichage d'un état d'erreur si le code est absent, invalide ou inconnu.

État actuel : cette logique n'est pas encore implémentée dans [assets/js/main.js](assets/js/main.js).

### Étape 5 - Afficher un formulaire personnalisé

Le formulaire RSVP ne doit pas être totalement générique. Il doit être capable de s'adapter en fonction de la catégorie de l'invité ou du foyer.

Exemples de personnalisation possibles :

- invités présents à toute la journée ;
- invités présents seulement à certains moments ;
- présence ou non d'enfants ;
- blocs conditionnels sur le repas, le brunch ou l'hébergement.

État actuel : un formulaire statique existe dans [index.html](index.html), mais il ne dépend pas encore des données Baserow.

### Étape 6 - Enregistrer la réponse RSVP

Quand l'invité valide le formulaire, le site doit transmettre les données au backend puis mettre à jour la ligne correspondante dans Baserow.

Le flux cible est :

1. contrôle côté client ;
2. envoi vers un endpoint PHP ;
3. validation serveur ;
4. appel API vers Baserow ;
5. retour d'un statut clair au front.

Sortie attendue : la ligne Baserow du foyer concerné est mise à jour de façon fiable et traçable.

État actuel : [php/send-mail.php](php/send-mail.php) gère un envoi d'email générique et ne couvre pas encore ce besoin.

### Étape 7 - Déclencher une notification

Après une réponse réussie, une alerte doit être envoyée afin de suivre les confirmations en temps réel.

Cette alerte peut être déclenchée :

- soit par le backend au moment du traitement ;
- soit par une automation N8n déclenchée après mise à jour Baserow.

Décision à prendre : choisir le point unique de responsabilité pour éviter des doublons d'email.

### Étape 8 - Relancer les non-répondants

Une routine planifiée devra identifier les foyers sans réponse et leur renvoyer un message de relance.

Cette étape devra préciser :

- la fréquence de contrôle ;
- les critères de sélection ;
- le contenu des relances ;
- le nombre maximal de relances ;
- la mise à jour des dates de relance dans Baserow.

Outil pressenti : N8n.

## Architecture technique

### Frontend

Le site repose sur :

- HTML ;
- SCSS et CSS ;
- JavaScript natif ;
- Pico CSS via CDN.

Fichiers concernés :

- [index.html](index.html) ;
- [assets/scss/style.scss](assets/scss/style.scss) ;
- [assets/css/style.css](assets/css/style.css) ;
- [assets/js/main.js](assets/js/main.js).

### Backend

Le backend repose sur PHP avec Composer.

Fichiers concernés :

- [php/send-mail.php](php/send-mail.php) ;
- [composer.json](composer.json) ;
- [vendor/autoload.php](vendor/autoload.php).

Bibliothèques déjà présentes ou prévues :

- PHPMailer pour les emails ;
- Guzzle pour les appels API ;
- phpdotenv pour les variables d'environnement.

### Configuration

La configuration SMTP actuelle est stockée dans [config/smtp.php](config/smtp.php), avec possibilité d'override par variables d'environnement.

Cette règle doit rester vraie pour la suite :

- configuration versionnée pour avancer rapidement ;
- variables d'environnement possibles pour les secrets ou les environnements futurs.

### Services externes

- Baserow : source de données invités et stockage des réponses ;
- N8n : automatisations et relances ;
- SMTP : diffusion des emails.

## État actuel du dépôt

### Front

- le site one-page est déjà amorcé ;
- plusieurs sections sont présentes ;
- un formulaire RSVP statique est visible ;
- la personnalisation par foyer n'est pas encore branchée.

### JavaScript

- le fichier [assets/js/main.js](assets/js/main.js) gère aujourd'hui le comportement de navigation et du menu mobile ;
- il ne gère pas encore la lecture du code URL, le chargement des données ni l'envoi RSVP.

### PHP

- le fichier [php/send-mail.php](php/send-mail.php) est actuellement un endpoint d'envoi d'email de contact ;
- il ne gère pas encore de logique métier liée au RSVP ;
- il peut néanmoins servir de base de structure pour un futur endpoint plus complet.

## Avancement réalisé (Lot 2 - Lot 3)

### Connexion à Baserow établie

✅ **Baserow instance configurée** :

- URL : https://baserow.symbios.dev/
- Database ID : 115
- Table ID : 338
- Endpoint API : `https://baserow.symbios.dev/api/database/rows/table/338/`
- Token d'authentification : stocké dans `.env` (phpdotenv)
- Query parameter requis : `?user_field_names=true` pour obtenir les noms de colonnes en clair

### Fichiers créés ou modifiés

✅ **[.env](/.env)** :

- Stockage sécurisé des variables de configuration (non versionné)
- Variables Baserow : BASEROW_API_URL, BASEROW_TOKEN, BASEROW_DATABASE_ID, BASEROW_TABLE_ID
- Note : les valeurs avec espaces doivent être entre guillemets (ex: `MAIL_FROM_NAME="Site Mariage"`)

✅ **[.env.example](/.env.example)** :

- Template documentin les variables nécessaires avec des commentaires explicatifs

✅ **[php/search-invite.php](php/search-invite.php)** :

- Endpoint POST pour chercher un invité par son nom complet
- Récupère les données directement depuis Baserow via l'API REST
- Gestion des deux noms du couple :
  - Cherche d'abord sur le champ `fullNameStarsky` (correspondance exacte, puis similarité)
  - Si pas de bon match (< 80% de similarité), bascule sur `fullNameHutch`
- Normalisation intelligente des noms : suppression accents, minuscules, collapsing d'espaces
- Utilise `similar_text()` pour la correspondance floue (fuzzy matching)
- Retourne au front : `{nom, email, code}` ou message d'erreur

✅ **[index.php](index.php)** :

- Formulaire de recherche avec champ `invite` (prénom et nom)
- **Contrôle côté client** : vérifie que l'utilisateur saisit au minimum 2 mots (prénom + nom)
- Si 1 seul mot : affiche message "Veuillez saisir votre prénom et votre nom (ex: Marie Dupont)" sans effacer la saisie
- Si trouvé : redirige vers `rsvp.php?code={code}&name={nom}&email={email}`
- Gestion des états : loading, erreur, succès
- Utilise Pico CSS pour le style

### Architecture de recherche

Le flux de recherche actuel :

1. **Front** : utilisateur saisit "Prénom Nom"
2. **Validation** : au minimum 2 mots requis
3. **Fetch** : POST vers `php/search-invite.php`
4. **Backend** :
   - Récupère tous les résultats de Baserow via l'API
   - Normalise les noms des deux champs (fullNameStarsky, fullNameHutch)
   - Effectue matching exact d'abord, puis fuzzy matching
   - Retourne le meilleur match trouvé
5. **Redirection** : vers formulaire RSVP avec les paramètres d'identification

### Champs Baserow utilisés

La table Baserow contient les champs suivants (parmi d'autres) :

- `fullNameStarsky` : nom du premier membre du couple
- `fullNameHutch` : nom du deuxième membre du couple
- `nom` : champ de secours/fallback
- `email` : adresse email pour contact
- `code` : code famille unique
- `famille`, `categorie`, `adulte`, `enfant`, `choix`, `nuit`, `consignes`, `musique` : données de réponse RSVP
- `dateEnvoi`, `dateConfirmation`, etc. : suivi temporel

## Marche à suivre d'implémentation

### Lot 1 - Stabiliser le modèle de données

Objectif : définir exactement les colonnes nécessaires dans Baserow.

À faire :

1. lister tous les champs nécessaires côté métier ;
2. décider quels champs sont saisis par les mariés et quels champs sont mis à jour par le site ;
3. définir les valeurs possibles des statuts ;
4. définir la règle d'unicité du code famille.

Critère de validation : une structure de table Baserow figée et documentée.

### Lot 2 - Définir le lien entre code famille et formulaire

Objectif : savoir comment un code permet de retrouver un foyer et de personnaliser l'interface.

À faire :

1. choisir le nom du paramètre d'URL ;
2. définir le format du code ;
3. définir le comportement si le code est introuvable ;
4. décider si le front appelle directement un endpoint interne ou si tout passe par le backend.

Critère de validation : une règle simple et unique d'identification des foyers.

### Lot 3 - Construire la récupération des données invité

Objectif : connecter le site à la donnée réelle.

À faire :

1. créer une couche backend pour appeler Baserow ;
2. sécuriser les tokens d'accès ;
3. exposer une réponse exploitable par le front ;
4. gérer les cas d'erreur de manière claire.

Critère de validation : à partir d'un code valide, le site récupère les informations du foyer.

### Lot 4 - Rendre le formulaire dynamique

Objectif : adapter l'affichage à la catégorie et à la composition du foyer.

À faire :

1. définir les blocs communs ;
2. définir les blocs conditionnels ;
3. préremplir les informations connues ;
4. afficher un état clair avant chargement, après chargement et en erreur.

Critère de validation : le formulaire affiché correspond au bon foyer et au bon niveau d'invitation.

### Lot 5 - Enregistrer les réponses

Objectif : persister la réponse dans Baserow sans ambiguïté.

À faire :

1. créer un endpoint dédié au RSVP ;
2. valider strictement les données reçues ;
3. mettre à jour la bonne ligne Baserow ;
4. renvoyer un message exploitable par le front.

Critère de validation : une soumission modifie bien la ligne attendue dans Baserow.

### Lot 6 - Gérer les notifications

Objectif : être averti lorsqu'une famille répond.

À faire :

1. choisir si la notification part depuis le backend ou N8n ;
2. définir le contenu du message ;
3. éviter les doublons ;
4. tracer le succès ou l'échec de l'envoi.

Critère de validation : une notification unique est reçue après une réponse réussie.

### Lot 7 - Gérer les relances

Objectif : automatiser le suivi des non-répondants.

À faire :

1. définir la fréquence ;
2. filtrer les foyers éligibles ;
3. envoyer la relance ;
4. enregistrer l'action dans Baserow.

Critère de validation : les foyers sans réponse sont repérés et relancés selon une règle documentée.

### Lot 8 - Sécuriser et configurer

Objectif : éviter de bloquer la suite avec une configuration instable.

À faire :

1. garder la configuration SMTP cohérente avec [config/smtp.php](config/smtp.php) ;
2. prévoir la configuration Baserow hors code versionné si nécessaire ;
3. documenter les variables d'environnement utiles ;
4. définir une stratégie minimale de validation des entrées.

Critère de validation : la configuration nécessaire pour exécuter le projet est claire et reproductible.

## Ordre recommandé de travail

Pour éviter les allers-retours inutiles, l'ordre recommandé est :

1. figer la structure Baserow ;
2. définir la logique du code famille ;
3. brancher une lecture réelle des données ;
4. rendre le formulaire dynamique ;
5. créer l'endpoint RSVP ;
6. ajouter les notifications ;
7. ajouter les relances N8n ;
8. finaliser la documentation technique.

## Questions ouvertes

Les points suivants restent à trancher :

- quelle sera la structure exacte de la table Baserow ;
- quelle sera la granularité des catégories d'invités ;
- est-ce qu'un foyer peut modifier sa réponse plusieurs fois ;
- quelle source déclenche la notification principale ;
- quel intervalle choisir pour les relances ;
- faut-il gérer un écran d'erreur ou de secours si le code famille n'est pas reconnu.

## Références du dépôt

- [README.md](README.md) : stack technique, structure et installation ;
- [index.html](index.html) : page one-page et formulaire actuel ;
- [assets/js/main.js](assets/js/main.js) : logique front actuelle ;
- [php/send-mail.php](php/send-mail.php) : backend email existant ;
- [config/smtp.php](config/smtp.php) : configuration SMTP.

## Point de reprise pour les prochaines sessions

Quand le travail reprendra, utiliser ce document dans l'ordre suivant :

1. relire l'objectif et le périmètre ;
2. vérifier la section état actuel du dépôt ;
3. reprendre le prochain lot non terminé dans la section marche à suivre d'implémentation ;
4. mettre à jour les questions ouvertes si une décision a été prise.

Prochaine étape logique : figer précisément la structure de la table Baserow avant d'attaquer l'implémentation du formulaire dynamique et du backend RSVP.
