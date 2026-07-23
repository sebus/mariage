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

✅ **COMPLÉTÉ**

La table Baserow (ID 338) contient toutes les données nécessaires :

- famille, email, code, catégorie, invites (JSON), confirmation, choix (multi-select), nuit (booléen), consignes, musique, dateConfirmation, etc.

Structure stable et exploitable.

### Étape 2 - Déclencher l'envoi initial des invitations

❌ **ANNULÉ**

Le QR code personnalisé sera imprimé dans le faire-part envoyé par voie postale.
L'envoi d'invitations par email n'est pas nécessaire à ce stade.

### Étape 3 - Générer le lien personnalisé

❌ **ANNULÉ**

Remplacé par un QR code imprimé dans le faire-part (cf. Étape 2).

### Étape 4 - Identifier la famille sur le site

✅ **COMPLÉTÉ**

L'identification se fait par **recherche textuelle** sur prénom et nom (via [php/search-invite.php](php/search-invite.php)).
Les sessions PHP stockent le code famille pour éviter l'exposition en URL.
Gestion d'erreur en place en cas de code absent ou invalide.

### Étape 5 - Afficher un formulaire personnalisé

✅ **COMPLÉTÉ**

Le formulaire [rsvp.php](rsvp.php) est entièrement dynamique :

- Pré-remplissage des données depuis Baserow
- Affichage conditionnel des invités avec leur statut (confirme/attente)
- Choix des événements adaptés à la catégorie (full → Repas, simple → Vin d'honneur uniquement)
- Pré-sélection des checkboxes et radio buttons depuis la base
- Préservation des consignes et musique
- **Champ commentaire optionnel** : textarea pour notes additionnelles (non enregistré en base, usage interne)

### Étape 6 - Enregistrer la réponse RSVP

✅ **COMPLÉTÉ**

L'endpoint [php/save-details-rsvp.php](php/save-details-rsvp.php) gère le cycle complet :

- Validation serveur des données
- Récupération du row ID via recherche Baserow
- Mise à jour du JSON invites avec les nouveaux statuts (confirme/attente)
- PATCH API vers Baserow avec tous les champs
- Traçabilité complète dans [debug.log](debug.log)

### Étape 7 - Déclencher une notification

✅ **COMPLÉTÉ**

Fonctionnalité entièrement implémentée avec templates personnalisés :

- Fonction `prepareEmailContent()` charge les templates et remplace les variables
- Fonction `sendRsvpNotification()` envoie deux emails distincts :
  1. **À l'invité** : utilise le template [assets/template/notification_rsvp.html](assets/template/notification_rsvp.html)
  2. **À mail_to** : utilise le template [assets/template/notification_copie.html](assets/template/notification_copie.html)
- **Formatage des choix** : conversion en labels français
  - `ceremonie` → **Cérémonie**
  - `vinHonneur` → **Vin d'honneur**
  - `repas` → **Repas et Soirée**
- **Champ commentaire** : textarea ajouté au formulaire (après musique)
  - Non enregistré en base de données (champ temporaire)
  - Transmis dans les notifications pour lecture interne
  - Apparaît ligne 4 de notification_copie.html pour suivi
- Remplacements supportés : [famille], [email], [code], [choix], [invites], [nuit], [consignes], [musique], [commentaire]
- Intégré dans [php/save-details-rsvp.php](php/save-details-rsvp.php) post-RSVP
- Trace complète dans [debug.log](debug.log)
- Utilise SMTP (boeuf.o2switch.net:465) avec timeout et gestion d'erreurs non-bloquante

### Étape 8 - Relancer les non-répondants

⏸️ **À FAIRE**

Une routine planifiée devra identifier les foyers sans réponse et leur renvoyer un message de relance.

Points à préciser :

- fréquence de contrôle ;
- critères de sélection ;
- contenu des relances ;
- nombre maximal de relances ;
- mise à jour des dates de relance dans Baserow.

Outil pressenti : N8n ou script PHP planifié.

### Étape 9 - Support multilingue (Français/Italien)

⏸️ **À FAIRE**

Système d'internationalisation pour adapter l'interface et les emails selon la langue du navigateur.

**Approche technique :**

- Détection automatique de `Accept-Language` HTTP header côté serveur
- Fallback sur français si langue non supportée
- Deux jeux de templates :
  - Interface [rsvp.php](rsvp.php) : labels, placeholders, messages
  - Emails : [assets/template/notification_rsvp.html](assets/template/notification_rsvp.html) et [assets/template/notification_copie.html](assets/template/notification_copie.html)

**Structure fichiers proposée :**

```
assets/i18n/
├── fr.php (strings françaises)
├── it.php (strings italiennes)
assets/template/
├── notification_rsvp_fr.html
├── notification_rsvp_it.html
├── notification_copie_fr.html
├── notification_copie_it.html
```

**Traductions à prévoir :**

1. Labels du formulaire : "Êtes-vous intéressé ?", "Indiquez le nombre", etc.
2. Légendes des events : "Cérémonie", "Vin d'honneur", "Repas et Soirée"
3. Messages de validation : "RSVP reçu", erreurs
4. Contenu emails : sujets et corps
5. Liens de modification : texte du CTA

**Considérations :**

- Stockage de la langue choisie en session après première détection
- Possibilité d'override manuel via parameter `?lang=it`
- Traçabilité de la langue dans [debug.log](debug.log)

### Étape 10 - Page de récapitulatif avec tableau

⏳ **EN COURS**

Création d'une page administrative d'accès restreint permettant de visualiser et piloter l'ensemble des réponses RSVP.

**Accès sécurisé :**

- Page accessible via URL protégée : [dashboard.php](dashboard.php)
- Authentification par mot de passe configuré dans `.env` : variable `DASHBOARD_PASSWORD`
- Vérification en session pour éviter les re-authentifications
- Redirection vers formulaire de connexion si non authentifié
- Timeout de session : 30 minutes d'inactivité

**Structure du tableau :**

| Colonne       | Type          | Source                                                 |
| ------------- | ------------- | ------------------------------------------------------ |
| Famille       | Texte         | Baserow - champ `famille`                              |
| Email         | Email         | Baserow - champ `email` (cliquable mailto)             |
| Confirmation  | Statut badge  | Baserow - champ `confirmation` (Oui/Non/En attente)    |
| Personnes     | Nombre        | Comptage des invités avec statut 'confirme'            |
| Cérémonie     | ✓/–           | Baserow - champ `choix` contient 'ceremonie'           |
| Vin d'honneur | ✓/–           | Baserow - champ `choix` contient 'vinHonneur'          |
| Repas         | ✓/–           | Baserow - champ `choix` contient 'repas'               |
| Nuit          | Oui/Non       | Baserow - champ `nuit` (booléen)                       |
| Date réponse  | Date formatée | Baserow - champ `dateConfirmation` (format JJ/MM/AAAA) |

**Fonctionnalités implémentées :**

1. **Authentification sécurisée** :
   - Formulaire login avec saisie password
   - Hash bcrypt stocké dans `.env` (jamais le plaintext)
   - Génération du hash : `php -r "echo password_hash('votre_mdp', PASSWORD_BCRYPT);"`
   - Endpoint [php/auth-dashboard.php](php/auth-dashboard.php) valide et crée la session

2. **Recherche dynamique** :
   - Champ de recherche textuelle sur la colonne "Famille"
   - Filtrage en temps réel (côté client avec JavaScript)
   - Insensible à la casse et aux accents (normalisation Unicode)
   - Affichage "Aucun résultat" si aucune correspondance

3. **Tableau récapitulatif** :
   - Données chargées via [php/get-dashboard-data.php](php/get-dashboard-data.php)
   - Pagination gérée côté API (100 résultats par page)
   - Parsing intelligent du JSON `invites` et du multi-select `choix`
   - Emails cliquables (mailto)
   - Statuts avec badges colorés (vert = Oui, rouge = Non, jaune = Attente)
   - Hover effect sur les lignes

4. **Totaux récapitulatifs** (footer du tableau) :
   - Nombre total de familles
   - Nombre de réponses "Oui" / "Non" / "En attente"
   - Nombre total de personnes confirmées
   - Nombre de confirmations pour chaque événement (Cérémonie, Vin d'honneur, Repas)
   - Nombre de demandes de nuit
   - **Taux de réponse global** : (réponses Oui + Non) / total familles × 100%

5. **Interface responsive** :
   - Design avec Pico CSS
   - Gradient header (violet)
   - Grille flexible pour les totaux (auto-fit)
   - Scroll horizontal pour le tableau sur mobile
   - Fonts réduites en mode mobile

6. **Traçabilité** :
   - Accès authentifiés loggés dans [debug.log](debug.log)
   - Événements : authentification réussie/échouée, chargement données
   - Erreurs API capturées et loggées

**Fichiers créés :**

- [dashboard.php](dashboard.php) : page d'affichage avec formulaire login et tableau
- [php/auth-dashboard.php](php/auth-dashboard.php) : endpoint d'authentification
- [php/get-dashboard-data.php](php/get-dashboard-data.php) : endpoint de récupération des données
- [assets/js/dashboard.js](assets/js/dashboard.js) : gestion du filtrage et de l'affichage

**Variables d'environnement utilisées :**

```
DASHBOARD_PASSWORD="<hash bcrypt du mot de passe>"
```

**Utilisation :**

1. Générer un hash sécurisé :

   ```bash
   php -r "echo password_hash('votre_mot_de_passe', PASSWORD_BCRYPT);"
   ```

2. Copier le hash dans [.env](.env) : `DASHBOARD_PASSWORD="<hash généré>"`

3. Accéder à [dashboard.php](dashboard.php), saisir le mot de passe

4. Tableau chargé avec données en temps réel depuis Baserow

5. Refresh automatique toutes les 5 minutes

### Étape 11 - Design UI, Test et Recettage

⏸️ **À FAIRE**

Système d'internationalisation pour adapter l'interface et les emails selon la langue du navigateur.

**Approche technique :**

- Détection automatique de `Accept-Language` HTTP header côté serveur
- Fallback sur français si langue non supportée
- Deux jeux de templates :
  - Interface [rsvp.php](rsvp.php) : labels, placeholders, messages
  - Emails : [assets/template/notification_rsvp.html](assets/template/notification_rsvp.html) et [assets/template/notification_copie.html](assets/template/notification_copie.html)

**Structure fichiers proposée :**

```
assets/i18n/
├── fr.php (strings françaises)
├── it.php (strings italiennes)
assets/template/
├── notification_rsvp_fr.html
├── notification_rsvp_it.html
├── notification_copie_fr.html
├── notification_copie_it.html
```

**Traductions à prévoir :**

1. Labels du formulaire : "Êtes-vous intéressé ?", "Indiquez le nombre", etc.
2. Légendes des events : "Cérémonie", "Vin d'honneur", "Repas et Soirée"
3. Messages de validation : "RSVP reçu", erreurs
4. Contenu emails : sujets et corps
5. Liens de modification : texte du CTA

**Considérations :**

- Stockage de la langue choisie en session après première détection
- Possibilité d'override manuel via parameter `?lang=it`
- Traçabilité de la langue dans [debug.log](debug.log)

### Étape 10 - Design UI, Test et Recettage

⏸️ **À FAIRE**

Phase de finalisation et de validation de l'ensemble du système avant mise en production.

**Aspects UI/UX :**

- Refinement du design responsive (mobile, tablette, desktop)
- Cohérence visuelle entre [index.html](index.html) et [rsvp.php](rsvp.php)
- États visuels clairs : loading, success, error (popovers)
- Accessibilité : contraste, labels explicites, navigation au clavier
- Optimisation des animations et du temps de réponse
- Branding et polish final

**Testing :**

1. **Fonctionnel** :
   - Flux complet : recherche → RSVP → email → confirmation
   - Pré-remplissage avec différentes catégories
   - Mise à jour des invites JSON avec tous les scénarios
   - Traitement des caractères accentués (français/italien)

2. **Email** :
   - Réception et rendu des deux templates (guest + internal)
   - Remplacement correct de tous les placeholders
   - Encodage UTF-8 respecté

3. **Edge cases** :
   - Code invalide ou expiré
   - Double soumission
   - Session expirée
   - Erreur réseau avec retry
   - Invites JSON malformé

4. **Performance** :
   - Temps de chargement du formulaire
   - Temps de soumission/réponse API
   - Charge serveur avec plusieurs soumissions

5. **Sécurité** :
   - Injection SQL (Baserow API sécurisée)
   - XSS (htmlspecialchars utilisé)
   - CSRF (session-based)
   - Exposure du code famille

**Recettage :**

- ✅ Prise en compte des retours utilisateurs
- ✅ Documentation utilisateur / FAQ
- ✅ Migration données Baserow finalisée
- ✅ Backups en place
- ✅ Plan de rollback défini

**Critères d'acceptation :**

- Tous les formulaires soumettent correctement
- Emails reçus et formatés correctement
- Données synchronisées en temps réel sur Baserow
- Aucune erreur JavaScript en console
- Responsive design validé sur 3 appareils
- Performance satisfaisante (<2s pour chaque action)

## État général du projet

| Étape                     | Statut      | Notes                             |
| ------------------------- | ----------- | --------------------------------- |
| 1. Données Baserow        | ✅ OK       | Structure figée et stable         |
| 2. Envoi invitations      | ❌ Annulé   | QR code papier                    |
| 3. Lien personnalisé      | ❌ Annulé   | Remplacé par QR code              |
| 4. Identification         | ✅ OK       | Recherche prénom/nom + session    |
| 5. Formulaire dynamique   | ✅ OK       | Pré-remplissage complet           |
| 6. Enregistrement RSVP    | ✅ OK       | API Baserow avec traçabilité      |
| 7. Notifications          | ✅ COMPLÉTÉ | SMTP configuré, phpmailer intégré |
| 8. Relance non-répondants | ⏸️ À FAIRE  | Nécessite N8n ou cron             |
| 9. Support multilingue    | ⏸️ À FAIRE  | FR/IT basé sur Accept-Language    |
| 10. Page récapitulatif    | ⏳ EN COURS | Tableau avec totaux + recherche   |
| 11. Design UI & Recettage | ⏸️ À FAIRE  | Polish final + tests complets     |

## Points d'attention

- **Logs** : tout est consigné dans [debug.log](debug.log) pour traçabilité complète
- **Sécurité** : code famille stocké en session, jamais exposé en URL
- **Baserow** : structure des champs multi-select bien comprise ({id, value, color})

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

## Démarrage du serveur local

Pour tester le projet en local, utiliser la commande PHP suivante depuis la racine du projet :

```bash
php -S localhost:8000
```

Puis accéder à `http://localhost:8000` dans le navigateur.

Alternative avec chemin explicite :

```bash
php -S localhost:8000 -t /run/media/seb_collet/SSD_500Go/mariage
```

Pour arrêter le serveur : `Ctrl+C`

## Point de reprise pour les prochaines sessions

Quand le travail reprendra, utiliser ce document dans l'ordre suivant :

1. relire l'objectif et le périmètre ;
2. vérifier la section état actuel du dépôt ;
3. reprendre le prochain lot non terminé dans la section marche à suivre d'implémentation ;
4. mettre à jour les questions ouvertes si une décision a été prise.

Prochaine étape logique : figer précisément la structure de la table Baserow avant d'attaquer l'implémentation du formulaire dynamique et du backend RSVP.

## Structure du JSON invité

```json
[
  { "prenom": "Maria", "statut": "confirme" },
  { "prenom": "Dino", "statut": "attente" },
  { "prenom": "Lorenzo", "statut": "confirme" },
  { "prenom": "Andrea", "statut": "confirme" }
]
```
