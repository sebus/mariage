# Mariage

Site one page et mobile first pour un mariage.

## Stack technique

- HTML / SCSS / CSS / JS / PHP
- Pico CSS (CDN)
- PHPMailer (Composer)

Pour les datas :

- Baserow

Les automations :

- N8n

## Structure

- `index.html`
- `assets/scss/style.scss`
- `assets/css/style.css`
- `assets/js/main.js`
- `config/smtp.php`
- `php/send-mail.php`
- `composer.json`

## Installation

1. Installer les dépendances PHP :

```bash
composer install
```

### Dépendances additionnelles (Baserow)

Installer les librairies utiles pour appeler l'API Baserow et gérer un fichier .env :

```bash
composer require guzzlehttp/guzzle
composer require vlucas/phpdotenv
```

2. Renseigner la configuration SMTP dans le repository :

Fichier : `config/smtp.php`

```php
<?php

return [
	'smtp_host' => 'smtp.example.com',
	'smtp_port' => 587,
	'smtp_user' => 'user@example.com',
	'smtp_pass' => 'votre_mot_de_passe',
	'mail_from' => 'user@example.com',
	'mail_from_name' => 'Site Mariage',
	'mail_to' => 'contact@example.com',
];
```
