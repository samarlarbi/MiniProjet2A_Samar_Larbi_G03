# MiniProjet2A — Réservation d'Événements - Samar Larbi G03
## ---- DEMO Video ----

https://drive.google.com/drive/folders/1Qp9WQlL5dSoLZ-0PFd4rX5w3hP1b8aH6?usp=sharing

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
git clone https://github.com/samarlarbi/MiniProjet2A_Samar_Larbi_G03.git
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


## Endpoints

### Authentification (publics)

| Méthode | URL | Description |
|---------|-----|-------------|
| POST | `/api/auth/register/options` | Génère le challenge WebAuthn pour l'inscription |
| POST | `/api/auth/register/verify` | Vérifie la passkey et crée le compte |
| POST | `/api/auth/login/options` | Génère le challenge WebAuthn pour la connexion |
| POST | `/api/auth/login/verify` | Vérifie la passkey et retourne les jetons JWT |
| POST | `/api/token/refresh` | Renouvelle le jeton JWT via le refresh token |

### Utilisateur authentifié (JWT requis)

| Méthode | URL | Description |
|---------|-----|-------------|
| GET | `/api/auth/me` | Retourne les infos de l'utilisateur connecté |
| GET | `/` | Liste tous les événements |
| GET | `/event/{id}` | Détail d'un événement |
| POST | `/event/{id}` | Réserver une place pour un événement |
### Administration (ROLE_ADMIN requis)

| Méthode | URL | Description |
|---------|-----|-------------|
| GET | `/admin/dashboard` | Tableau de bord administrateur |
| GET/POST | `/admin/event/new` | Créer un événement |
| GET/POST | `/admin/event/{id}/edit` | Modifier un événement |
| POST | `/admin/event/{id}` | Supprimer un événement |
| GET | `/admin/event/{id}/reservations` | Voir les réservations d'un événement |

## Utilisation

### Créer un compte
Aller sur `/register`, entrer un email et confirmer avec Windows Hello ou la biométrie du navigateur.

### Accès administrateur
Après inscription, promouvoir un compte via la console :
```bash
php bin/console doctrine:query:sql "UPDATE user SET roles='[\"ROLE_ADMIN\",\"ROLE_USER\"]' WHERE email='votre@email.com'"
```

Puis accéder à `/admin/dashboard`.

