---
description: "Rédiger un message de commit au format Conventional Commits"
name: "Commit Message"
argument-hint: "Description des modifications (ex: ajouter validation du code famille, corriger le formulaire RSVP)"
agent: "agent"
---

Tu es un expert en gestion de code. Rédige un message de commit au format **Conventional Commits** basé sur les modifications décrites ci-dessous.

## Format requis

```
<type>(<scope>): <sujet>

<description>

<footer>
```

### Directives

**Type** (obligatoire - en minuscules) :

- `feat`: une nouvelle fonctionnalité
- `fix`: correction d'un bug
- `docs`: modifications de documentation
- `style`: formatage, missing semicolons, etc. (pas de changement fonctionnel)
- `refactor`: refonte de code sans changer la fonctionnalité
- `perf`: amélioration des performances
- `test`: ajout ou modification de tests
- `chore`: tâche de maintenance (dépendances, outils, configs)

**Scope** (optionnel, en minuscules, séparé par des tirets) : ex `formulaire`, `baserow-api`, `notification-email`

**Sujet** (obligatoire, 50 caractères max) :

- Impératif présent ("ajouter" et non "ajouté")
- Commencer par une minuscule
- Pas de point final

**Description** (optionnelle, 72 caractères max par ligne) :

- Expliquer le _pourquoi_, pas le _quoi_
- Peut contenir plusieurs paragraphes

**Footer** (optionnelle) :

- Indiquer les issues fermées : `Closes #123` ou `Fixes #456`
- Notes importantes : `BREAKING CHANGE: description`

### Exemple

```
feat(formulaire): ajouter validation du code famille

La validation du code famille se fait au chargement de la page.
Si le code est absent ou invalide, un message d'erreur est affiché.

Closes #12
```

## Ta tâche

Voici les modifications à résumer :

{changements}

Génère le message de commit correspondant. Fournis uniquement le message, pas d'explications supplémentaires.
