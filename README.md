# SaintAgnes 2.0 - Plateforme de Gestion de la Conformité RGPD

## Table des Matières

- [À Propos](#à-propos)
- [Fonctionnalités Principales](#fonctionnalités-principales)
- [Architecture](#architecture)
- [Technologies Utilisées](#technologies-utilisées)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration](#configuration)
- [Utilisation](#utilisation)
- [API Documentation](#api-documentation)
- [Structure du Projet](#structure-du-projet)
- [Sécurité](#sécurité)
- [Déploiement](#déploiement)
- [Tests](#tests)
- [Contribution](#contribution)
- [Support](#support)

## À Propos

**SaintAgnes 2.0** est une plateforme complète de gestion de la conformité RGPD (Règlement Général sur la Protection des Données) conçue pour aider les organisations à :

- Maintenir un registre des traitements de données personnelles
- Gérer les incidents de violation de données
- Mettre en œuvre des workflows de validation
- Assurer la conformité aux exigences réglementaires
- Communiquer avec les parties prenantes via un système de notifications

L'application suit une architecture moderne full-stack avec une séparation claire entre le frontend (React + TypeScript) et le backend (Symfony 7.3).

## Fonctionnalités Principales

### 1. Gestion des Traitements RGPD

- **Création et édition de traitements** : Formulaire multi-étapes couvrant tous les aspects RGPD
- **Workflow de validation** :
  - L'utilisateur crée un traitement (statut : Brouillon)
  - Soumission pour validation (statut : En validation)
  - Le DPO examine et approuve ou demande des modifications
  - Statuts finaux : Validé, À modifier, ou Archivé

- **Données capturées** :
  - Responsable du traitement et coordonnées
  - Finalité et base juridique
  - Catégories de données et volumes
  - Personnes concernées (y compris personnes vulnérables)
  - Destinataires internes/externes
  - Sous-traitants et hébergement
  - Transferts UE/hors UE
  - Durées de conservation
  - Mesures de sécurité (physiques et logiques)
  - Mise en œuvre des droits des personnes concernées

### 2. Gestion des Violations de Données

- Enregistrement et suivi des incidents
- Évaluation de la gravité (faible/moyenne/élevée)
- Suivi de l'implication de données personnelles
- Comptage des personnes affectées
- Évaluation des risques
- Workflow d'examen par le DPO
- Suivi des notifications aux autorités
- Suivi des notifications aux personnes concernées
- Actions de confinement et de remédiation
- Suivi des statuts (ouvert → en examen → notifié → clôturé)

### 3. Système d'Authentification et d'Autorisation

- Authentification basée sur JWT (JSON Web Tokens)
- Trois niveaux de rôles :
  - **ROLE_USER** : Utilisateurs standards qui créent les traitements
  - **ROLE_DPO** : Délégués à la Protection des Données qui valident les traitements
  - **ROLE_ADMIN** : Accès complet au système
- Mécanisme de rafraîchissement de token
- Routes protégées avec contrôle d'accès basé sur les rôles

### 4. Système de Notifications

- Notifications in-app
- Notifications par email avec templates HTML
- Types de notifications :
  - Soumission de traitement (vers le DPO)
  - Validation de traitement (vers l'utilisateur)
  - Demandes de modification (vers l'utilisateur)
- Suivi lu/non lu

### 5. Gestion des Utilisateurs

- Les administrateurs peuvent créer/modifier/supprimer des utilisateurs
- Attribution des rôles
- Identification basée sur l'email
- Validation de la force du mot de passe

### 6. Audit et Journalisation

- Journalisation des activités pour la conformité
- Suivi des modifications de traitements
- Journalisation des actions utilisateurs

## Architecture

### Vue d'Ensemble

```
SaintAgnes2.0/
├── backEnd/        # API Symfony 7.3
│   ├── config/     # Configuration Symfony
│   ├── src/        # Code source
│   │   ├── Controller/   # Contrôleurs API
│   │   ├── Entity/       # Entités Doctrine
│   │   ├── Repository/   # Repositories
│   │   ├── Service/      # Services métier
│   │   └── Command/      # Commandes console
│   ├── migrations/ # Migrations de base de données
│   └── public/     # Point d'entrée public
│
└── frontEnd/       # Application React + TypeScript
    ├── src/
    │   ├── components/   # Composants React
    │   ├── pages/        # Pages de l'application
    │   ├── services/     # Services API
    │   ├── stores/       # Gestion d'état Zustand
    │   └── types/        # Définitions TypeScript
    └── public/           # Assets statiques
```

### Schéma de Base de Données

**Base de données PostgreSQL** : `BD_RGPD_Saint_Agnes_2024`

**Tables principales** :
- `users` : Comptes utilisateurs avec rôles
- `treatments` : Enregistrements des traitements RGPD
- `notifications` : Notifications utilisateurs
- `data_breach_incident` : Suivi des violations de données
- `doctrine_migration_versions` : Contrôle de version du schéma

## Technologies Utilisées

### Backend

- **PHP** : 8.2+
- **Symfony** : 7.3.*
- **Base de données** : PostgreSQL 15/16
- **Authentification** : JWT (Lexik JWT Authentication Bundle)
- **ORM** : Doctrine ORM 3.5
- **Email** : Symfony Mailer avec support MailerSend/Mailtrap
- **Sécurité** : Hachage de mots de passe, support CORS (Nelmio)

### Frontend

- **React** : 18.3.1
- **TypeScript** : 5.5.3
- **Outil de build** : Vite 5.4.2
- **Styles** : TailwindCSS 3.4.1
- **Gestion d'état** : Zustand 4.4.6 avec middleware persist
- **Routing** : React Router DOM 6.26.0
- **Client HTTP** : Axios 1.6.0
- **Formulaires** : React Hook Form 7.47.0
- **Notifications UI** : React Hot Toast 2.4.1
- **Génération PDF** : jsPDF + jspdf-autotable

## Prérequis

### Pour le développement local

- **PHP** 8.2 ou supérieur
- **Composer** 2.x
- **Node.js** 18.x ou supérieur
- **npm** ou **yarn**
- **PostgreSQL** 15 ou supérieur
- **Symfony CLI** (recommandé)

### Avec Docker

- **Docker** 20.x ou supérieur
- **Docker Compose** 2.x ou supérieur

## Installation

### Option 1 : Installation locale

#### 1. Cloner le repository

```bash
git clone https://github.com/triomphant75/RGPD-Manager.git
cd SaintAgnes2.0
```

#### 2. Installer le backend

```bash
cd backEnd

# Installer les dépendances PHP
composer install

# Créer le fichier .env.local et configurer les variables
cp .env .env.local
# Éditez .env.local avec vos paramètres

# Générer les clés JWT
php bin/console lexik:jwt:generate-keypair

# Créer la base de données
php bin/console doctrine:database:create

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# Démarrer le serveur Symfony
symfony server:start
# Ou avec PHP built-in server:
# php -S localhost:8000 -t public
```

#### 3. Installer le frontend

```bash
cd ../frontEnd

# Installer les dépendances Node
npm install

# Démarrer le serveur de développement
npm run dev
```

### Option 2 : Installation avec Docker

```bash
# Démarrer tous les services
docker-compose up -d

# Exécuter les migrations
docker-compose exec php php bin/console doctrine:migrations:migrate

# Générer les clés JWT
docker-compose exec php php bin/console lexik:jwt:generate-keypair
```

## Configuration

### Variables d'Environnement Backend

Créez un fichier `.env.local` dans le dossier `backEnd/` :

```env
# Database
DATABASE_URL="postgresql://username:password@localhost:5432/BD_RGPD_Saint_Agnes_2024?serverVersion=15&charset=utf8"

# JWT Authentication
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase_here

# Mailer
MAILER_DSN=smtp://username:password@smtp.mailtrap.io:2525
MAILER_FROM_ADDRESS=noreply@saintagnes.com
MAILER_FROM_NAME="SaintAgnes RGPD"

# App
APP_ENV=dev
APP_SECRET=your_app_secret_here

# CORS
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

### Variables d'Environnement Frontend

Créez un fichier `.env` dans le dossier `frontEnd/` :

```env
VITE_API_URL=http://localhost:8000/api
```

### Configuration de la Base de Données

1. Créez une base de données PostgreSQL nommée `BD_RGPD_Saint_Agnes_2024`
2. Mettez à jour `DATABASE_URL` dans `.env.local`
3. Exécutez les migrations : `php bin/console doctrine:migrations:migrate`

### Configuration des Emails

Pour le développement, utilisez Mailtrap :

1. Créez un compte sur [Mailtrap.io](https://mailtrap.io)
2. Récupérez vos identifiants SMTP
3. Mettez à jour `MAILER_DSN` dans `.env.local`

Pour la production, utilisez MailerSend ou un autre service SMTP.

Consultez la documentation détaillée : [backEnd/docs/MAILER_MAILTRAP_DEVELOPPEMENT.md](backEnd/docs/MAILER_MAILTRAP_DEVELOPPEMENT.md)

### Tester la Configuration Email

```bash
cd backEnd
php bin/console app:test-email votre-email@exemple.com
```

## Utilisation

### Accéder à l'Application

- **Frontend** : [http://localhost:5173](http://localhost:5173)
- **Backend API** : [http://localhost:8000/api](http://localhost:8000/api)

### Créer le Premier Utilisateur Administrateur

```bash
cd backEnd

# Via la console Symfony (créer une commande personnalisée)
# Ou directement via l'API de registration
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@saintagnes.com",
    "password": "VotreMotDePasseSecurise123!",
    "roles": ["ROLE_ADMIN"]
  }'
```

### Workflow Typique

1. **Connexion** : L'utilisateur se connecte avec son email et mot de passe
2. **Tableau de bord** : Affichage des traitements et notifications
3. **Créer un traitement** : Remplir le formulaire multi-étapes
4. **Soumettre pour validation** : Le DPO reçoit une notification
5. **Validation DPO** : Le DPO examine et valide ou demande des modifications
6. **Archivage** : Les traitements obsolètes peuvent être archivés

### Commandes Console Utiles

```bash
# Tester l'encryption
php bin/console app:test-encryption

# Générer une clé d'encryption
php bin/console app:generate-encryption-key

# Envoyer des rappels de violation de données
php bin/console app:breach-notification-reminder

# Lister les utilisateurs
php bin/console app:list-users

# Vider le cache
php bin/console cache:clear
```

## API Documentation

### Authentification

#### POST `/api/auth/register`
Enregistrer un nouvel utilisateur

**Body** :
```json
{
  "email": "user@example.com",
  "password": "SecurePassword123!",
  "roles": ["ROLE_USER"]
}
```

**Response** :
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "roles": ["ROLE_USER"]
  }
}
```

#### POST `/api/auth/login`
Connexion utilisateur

**Body** :
```json
{
  "email": "user@example.com",
  "password": "SecurePassword123!"
}
```

**Response** :
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "def502004d8f...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "roles": ["ROLE_USER"]
  }
}
```

#### GET `/api/auth/me`
Obtenir les informations de l'utilisateur connecté

**Headers** : `Authorization: Bearer <token>`

**Response** :
```json
{
  "id": 1,
  "email": "user@example.com",
  "roles": ["ROLE_USER"]
}
```

### Traitements

#### GET `/api/treatments`
Lister tous les traitements de l'utilisateur

**Headers** : `Authorization: Bearer <token>`

#### GET `/api/treatments/pending-validation`
Lister les traitements en attente de validation (DPO uniquement)

**Headers** : `Authorization: Bearer <token>`

#### GET `/api/treatments/{id}`
Obtenir les détails d'un traitement

**Headers** : `Authorization: Bearer <token>`

#### POST `/api/treatments`
Créer un nouveau traitement

**Headers** : `Authorization: Bearer <token>`

**Body** : Voir la structure complète dans [src/Entity/Treatment.php](backEnd/src/Entity/Treatment.php)

#### PUT `/api/treatments/{id}`
Mettre à jour un traitement

**Headers** : `Authorization: Bearer <token>`

#### DELETE `/api/treatments/{id}`
Supprimer un traitement

**Headers** : `Authorization: Bearer <token>`

#### POST `/api/treatments/{id}/submit`
Soumettre un traitement pour validation

**Headers** : `Authorization: Bearer <token>`

#### POST `/api/treatments/{id}/validate`
Valider un traitement (DPO uniquement)

**Headers** : `Authorization: Bearer <token>`

**Body** :
```json
{
  "comment": "Commentaire de validation"
}
```

#### POST `/api/treatments/{id}/request-modification`
Demander des modifications (DPO uniquement)

**Headers** : `Authorization: Bearer <token>`

**Body** :
```json
{
  "comment": "Raison des modifications demandées"
}
```

#### POST `/api/treatments/{id}/archive`
Archiver un traitement

**Headers** : `Authorization: Bearer <token>`

### Utilisateurs (Admin uniquement)

#### GET `/api/users`
Lister tous les utilisateurs

**Headers** : `Authorization: Bearer <token>`

#### POST `/api/users`
Créer un utilisateur

**Headers** : `Authorization: Bearer <token>`

#### PUT `/api/users/{id}`
Mettre à jour un utilisateur

**Headers** : `Authorization: Bearer <token>`

#### DELETE `/api/users/{id}`
Supprimer un utilisateur

**Headers** : `Authorization: Bearer <token>`

### Notifications

#### GET `/api/notifications`
Obtenir les notifications de l'utilisateur

**Headers** : `Authorization: Bearer <token>`

#### POST `/api/notifications/{id}/mark-read`
Marquer une notification comme lue

**Headers** : `Authorization: Bearer <token>`

### Violations de Données

#### GET `/api/data-breaches`
Lister les incidents de violation de données

**Headers** : `Authorization: Bearer <token>`

#### POST `/api/data-breaches`
Créer un nouvel incident

**Headers** : `Authorization: Bearer <token>`

#### GET `/api/data-breaches/{id}`
Obtenir les détails d'un incident

**Headers** : `Authorization: Bearer <token>`

#### PUT `/api/data-breaches/{id}`
Mettre à jour un incident

**Headers** : `Authorization: Bearer <token>`

## Structure du Projet

### Backend (Symfony)

```
backEnd/
├── bin/                    # Scripts exécutables
├── config/                 # Configuration Symfony
│   ├── packages/          # Configuration des bundles
│   ├── routes/            # Routes API
│   └── jwt/               # Clés JWT
├── migrations/            # Migrations Doctrine
├── public/                # Point d'entrée web
├── src/
│   ├── Command/          # Commandes console
│   │   ├── BreachNotificationReminderCommand.php
│   │   ├── GenerateEncryptionKeyCommand.php
│   │   ├── TestEmailCommand.php
│   │   └── TestEncryptionCommand.php
│   ├── Controller/       # Contrôleurs API
│   │   ├── AuthController.php
│   │   ├── DataBreachController.php
│   │   ├── NotificationController.php
│   │   ├── RefreshTokenController.php
│   │   ├── TreatmentController.php
│   │   └── UserController.php
│   ├── Entity/           # Entités Doctrine
│   │   ├── DataBreachIncident.php
│   │   ├── Notification.php
│   │   ├── Treatment.php
│   │   └── User.php
│   ├── Repository/       # Repositories Doctrine
│   ├── Service/          # Services métier
│   │   ├── AuditLogger.php
│   │   ├── DataBreachService.php
│   │   ├── EncryptionService.php
│   │   ├── NotificationService.php
│   │   └── TreatmentValidator.php
│   └── Kernel.php        # Kernel Symfony
├── templates/            # Templates Twig (emails)
│   └── email/
│       └── notification.html.twig
├── composer.json         # Dépendances PHP
└── .env                  # Variables d'environnement
```

### Frontend (React + TypeScript)

```
frontEnd/
├── public/               # Assets statiques
├── src/
│   ├── components/      # Composants React
│   │   ├── Layout.tsx
│   │   ├── Navbar.tsx
│   │   ├── TreatmentForm.tsx
│   │   ├── TreatmentTable.tsx
│   │   └── TreatmentDetail.tsx
│   ├── pages/           # Pages de l'application
│   │   ├── Dashboard.tsx
│   │   ├── DPODashboard.tsx
│   │   ├── Login.tsx
│   │   ├── Notifications.tsx
│   │   ├── Treatments.tsx
│   │   └── Users.tsx
│   ├── services/        # Services API
│   │   └── api.ts
│   ├── stores/          # Gestion d'état Zustand
│   │   ├── authStore.ts
│   │   └── treatmentStore.ts
│   ├── types/           # Définitions TypeScript
│   │   └── index.ts
│   ├── App.tsx          # Composant racine
│   └── main.tsx         # Point d'entrée
├── index.html           # HTML de base
├── package.json         # Dépendances Node
├── tsconfig.json        # Configuration TypeScript
├── vite.config.ts       # Configuration Vite
└── tailwind.config.js   # Configuration TailwindCSS
```

## Sécurité

### Mesures de Sécurité Implémentées

1. **Authentification JWT** : Tokens avec expiration et mécanisme de refresh
2. **Hachage de mots de passe** : Utilisation de l'algorithme Symfony auto (bcrypt/argon2)
3. **Protection CORS** : Configuration pour empêcher les accès non autorisés
4. **Protection CSRF** : Tokens CSRF pour les requêtes sensibles
5. **Encryption de données** : Service d'encryption disponible pour les données sensibles
6. **Validation des entrées** : Validation côté serveur complète
7. **Protection contre les injections SQL** : Requêtes paramétrées via Doctrine ORM
8. **Protection XSS** : Échappement automatique de React

### Bonnes Pratiques de Sécurité

- Changez régulièrement les clés JWT
- Utilisez HTTPS en production
- Définissez des mots de passe forts (minimum 8 caractères, majuscules, minuscules, chiffres, caractères spéciaux)
- Limitez les tentatives de connexion
- Activez les logs d'audit
- Sauvegardez régulièrement la base de données
- Gardez les dépendances à jour

### Rotation des Clés JWT

```bash
# Régénérer les clés JWT
cd backEnd
php bin/console lexik:jwt:generate-keypair --overwrite

# Redémarrer l'application
symfony server:stop
symfony server:start
```

## Déploiement

### Préparation pour la Production

#### Backend

1. **Mettre à jour les variables d'environnement** :

```env
APP_ENV=prod
APP_DEBUG=0
DATABASE_URL="postgresql://user:pass@prod-host:5432/db_name"
CORS_ALLOW_ORIGIN='^https://votre-domaine\.com$'
```

2. **Optimiser l'autoloader** :

```bash
composer install --no-dev --optimize-autoloader
```

3. **Vider et préchauffer le cache** :

```bash
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

4. **Exécuter les migrations** :

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

#### Frontend

1. **Mettre à jour l'URL de l'API** :

```env
VITE_API_URL=https://api.votre-domaine.com/api
```

2. **Build de production** :

```bash
npm run build
```

3. **Déployer le dossier `dist/`** vers votre serveur web

### Déploiement avec Docker

```bash
# Build des images de production
docker-compose -f docker-compose.prod.yml build

# Démarrer les services
docker-compose -f docker-compose.prod.yml up -d

# Exécuter les migrations
docker-compose -f docker-compose.prod.yml exec php php bin/console doctrine:migrations:migrate --no-interaction
```

### Configuration du Serveur Web

#### Apache

```apache
<VirtualHost *:80>
    ServerName votre-domaine.com
    DocumentRoot /var/www/SaintAgnes2.0/backEnd/public

    <Directory /var/www/SaintAgnes2.0/backEnd/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/saintagnes_error.log
    CustomLog ${APACHE_LOG_DIR}/saintagnes_access.log combined
</VirtualHost>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name votre-domaine.com;
    root /var/www/SaintAgnes2.0/backEnd/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }

    error_log /var/log/nginx/saintagnes_error.log;
    access_log /var/log/nginx/saintagnes_access.log;
}
```

## Tests

### Tests Backend (PHPUnit)

```bash
cd backEnd

# Exécuter tous les tests
php bin/phpunit

# Exécuter des tests spécifiques
php bin/phpunit tests/Controller/TreatmentControllerTest.php

# Avec couverture de code
php bin/phpunit --coverage-html var/coverage
```

### Tests Frontend (Vitest)

```bash
cd frontEnd

# Exécuter tous les tests
npm run test

# Mode watch
npm run test:watch

# Couverture de code
npm run test:coverage
```

### Tests d'Intégration

```bash
# Tester la connexion à la base de données
cd backEnd
php bin/console doctrine:query:sql "SELECT 1"

# Tester l'envoi d'emails
php bin/console app:test-email test@example.com

# Tester l'encryption
php bin/console app:test-encryption
```

## Contribution

### Processus de Contribution

1. Fork le projet
2. Créer une branche de fonctionnalité (`git checkout -b feature/AmazingFeature`)
3. Commiter vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

### Standards de Code

#### Backend (PHP)

- Suivre les [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards
- Utiliser PHP CS Fixer :

```bash
composer require --dev friendsofphp/php-cs-fixer
vendor/bin/php-cs-fixer fix src
```

#### Frontend (TypeScript)

- Suivre les conventions ESLint
- Exécuter le linter :

```bash
npm run lint
npm run lint:fix
```

### Conventions de Commit

- `feat:` Nouvelle fonctionnalité
- `fix:` Correction de bug
- `docs:` Documentation
- `style:` Formatage, points-virgules manquants, etc.
- `refactor:` Refactoring de code
- `test:` Ajout de tests
- `chore:` Maintenance

Exemple : `feat: add data breach incident reporting`

## Support

### Documentation

- Documentation backend : [backEnd/docs/](backEnd/docs/)
- Documentation Symfony : [https://symfony.com/doc/current/index.html](https://symfony.com/doc/current/index.html)
- Documentation React : [https://react.dev/](https://react.dev/)

### Contact

- **Développeur principal** : Triomphant Aldi NZIKOU
- **Email** : triomphantaldi@gmail.com
- **Projet GitHub** : https://github.com/triomphant75/RGPD-Manager.git

### Problèmes Connus

- Les clés JWT doivent être régénérées après un clone du repository
- La configuration CORS doit être mise à jour pour la production
- Les emails nécessitent une configuration SMTP valide


## Licence


---

**Version** : 2.0
**Dernière mise à jour** : 2025
**Statut** : En développement actif

---

Développé avec soin pour faciliter la conformité RGPD.
