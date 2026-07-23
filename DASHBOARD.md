# 📊 Dashboard RSVP - Guide d'utilisation

Le dashboard est une page administrative permettant de consulter l'ensemble des réponses RSVP en un coup d'œil.

## 🔐 Accès sécurisé

### Configuration initiale

1. **Générer un mot de passe hachifié** :

   ```bash
   php -r "echo password_hash('votre_mot_de_passe', PASSWORD_BCRYPT);"
   ```

2. **Copier le hash dans `.env`** :

   ```env
   DASHBOARD_PASSWORD="<hash_généré_ici>"
   ```

3. **Le mot de passe par défaut est** : `mariage2027`
   - Hash déjà configuré dans `.env`

### Accès à la page

1. Ouvrir [dashboard.php](dashboard.php) dans votre navigateur
2. Saisir le mot de passe
3. Cliquer sur "Se connecter"

La session reste active pendant **30 minutes** d'inactivité.

## 📋 Tableau récapitulatif

### Colonnes affichées

| Colonne           | Description                                 |
| ----------------- | ------------------------------------------- |
| **Famille**       | Nom de la famille (cliquable = recherche)   |
| **Email**         | Adresse email (lien mailto)                 |
| **Confirmation**  | Oui / Non / En attente (avec badge couleur) |
| **Personnes**     | Nombre de personnes confirmées              |
| **Cérémonie**     | ✓ ou – selon participation                  |
| **Vin d'honneur** | ✓ ou – selon participation                  |
| **Repas**         | ✓ ou – selon participation                  |
| **Nuit**          | Oui / Non selon demande                     |
| **Date réponse**  | Date au format JJ/MM/AAAA                   |

### Statuts par couleur

- 🟢 **Vert** = Confirmation : Oui
- 🔴 **Rouge** = Confirmation : Non
- 🟡 **Jaune** = En attente

## 🔍 Recherche dynamique

1. Utiliser le champ "🔍 Rechercher par nom de famille..." en haut
2. La recherche s'effectue en **temps réel**
3. **Insensible à la casse et aux accents**
   - Chercher "Dupont" trouvera "dupont", "DUPONT", "Dûpônt"
4. Cliquer sur un email pour l'envoyer un message

## 📈 Totaux récapitulatifs

En bas du tableau, une section "Résumé" affiche :

- **Familles** : nombre total de familles
- **Confirmations (Oui)** : nombre de familles ayant dit "Oui"
- **Confirmations (Non)** : nombre de familles ayant dit "Non"
- **En attente** : nombre de foyers sans réponse
- **Taux de réponse** : pourcentage global de réponses (familles)
- **Personnes confirmées** : nombre total de personnes ayant dit "Oui"
- **Cérémonie** : nombre de **personnes** confirmées pour la cérémonie
- **Vin d'honneur** : nombre de **personnes** confirmées pour le vin d'honneur
- **Repas** : nombre de **personnes** confirmées pour le repas
- **Demandes de nuit** : nombre de **personnes** voulant rester la nuit

> 📌 **Important** : Les KPI Cérémonie, Vin d'honneur, Repas et Nuit sont multipliés par le nombre de personnes par foyer, pas juste le nombre de familles.

## 🔄 Rafraîchissement automatique

- Le tableau se **rafraîchit automatiquement toutes les 5 minutes**
- Vous pouvez aussi rafraîchir manuellement avec F5

## 🛡️ Sécurité

- Mot de passe **hachifié en bcrypt** (jamais stocké en clair)
- **Session timeout** après 30 minutes d'inactivité
- Les données sont récupérées en **temps réel depuis Baserow**
- Tous les accès sont **loggés dans `debug.log`**

## 🐛 Dépannage

### "Mot de passe incorrect"

- Vérifier que `DASHBOARD_PASSWORD` est configuré dans `.env`
- Le hash doit être entre guillemets dans `.env`

### "Session expirée"

- Vous avez quitté le navigateur depuis plus de 30 minutes
- Cliquer sur le lien de login ou se reconnecter

### Tableau vide

- Vérifier que les variables Baserow sont correctes :
  - `BASEROW_API_URL`
  - `BASEROW_TOKEN`
  - `BASEROW_TABLE_ID`
- Consulter `debug.log` pour les erreurs API

## 📝 Fichiers associés

- [dashboard.php](dashboard.php) - Page principale
- [php/auth-dashboard.php](php/auth-dashboard.php) - Authentification
- [php/get-dashboard-data.php](php/get-dashboard-data.php) - Récupération des données
- [assets/js/dashboard.js](assets/js/dashboard.js) - Filtrage et affichage
- [debug.log](debug.log) - Logs de tous les accès et erreurs
