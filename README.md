# MiniProjet2A — Réservation d'Événements - Samar Larbi G03


## Présentation

Application web de réservation d'événements développée dans le cadre du Mini Projet 2A.
Les utilisateurs peuvent consulter les événements disponibles et réserver des places.
L'authentification repose sur les Passkeys (WebAuthn) combinées aux jetons JWT — pas de mot de passe.

L'interface administrateur permet de créer, modifier et supprimer des événements,
et de consulter la liste des réservations par événement.

## Stack technique

- Symfony 7 / PHP 8.2
- SQLite (local) ou PostgreSQL (Docker)
- WebAuthn via `web-auth/webauthn-symfony-bundle`
- JWT via `lexik/jwt-authentication-bundle`
- Twig + JavaScript natif + CSS (glassmorphism)

## Installation

### Prérequis
- PHP 8.2 avec les extensions `pdo_sqlite`, `openssl`, `gmp`, `sodium`
- Composer

### Étapes
```bash
git clone https://github.com/samarlarbi/MiniProjet2A-Reservation-evenements---Samar-Larbi-G03.git
cd MiniProjet2A-EventReservation
composer install
cp .env .env.local
php bin/console lexik:jwt:generate-keypair
php bin/console doctrine:migrations:migrate -n
```

Démarrer le serveur :
```bash
#  Symfony CLI 
symfony server:start

# Ou avec PHP intégré
php -S localhost:8000 -t public
```

Accéder à l'application : `https://localhost:8000`

### Avec Docker
```bash
docker compose up -d --build
```

Application disponible sur `http://localhost:8000`.

## Utilisation

### Créer un compte
Aller sur `/register`, entrer un email et confirmer avec Windows Hello ou la biométrie du navigateur.

### Accès administrateur
Après inscription, promouvoir un compte via la console :
```bash
php bin/console doctrine:query:sql "UPDATE user SET roles='[\"ROLE_ADMIN\",\"ROLE_USER\"]' WHERE email='votre@email.com'"
```

Puis accéder à `/admin/dashboard`.

