# Documentation Technique - Check du Matin

## Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Architecture technique](#architecture-technique)
3. [Technologies utilisées](#technologies-utilisées)
4. [Structure du projet](#structure-du-projet)
5. [Base de données](#base-de-données)
6. [Modèles et relations](#modèles-et-relations)
7. [Contrôleurs et routes](#contrôleurs-et-routes)
8. [Fonctionnalités principales](#fonctionnalités-principales)
9. [Système d'authentification et permissions](#système-dauthentification-et-permissions)
10. [Configuration et déploiement](#configuration-et-déploiement)
11. [Maintenance et tâches planifiées](#maintenance-et-tâches-planifiées)
12. [Sécurité](#sécurité)
13. [Dépannage](#dépannage)

---

## Vue d'ensemble

**Check du Matin** est une application web de gestion de vérifications de services pour clients. Elle permet aux gestionnaires et administrateurs de suivre, vérifier et documenter l'état des services de leurs clients via des "checks" (vérifications) régulières.

### Objectifs principaux

- Gestion multi-clients avec isolation des données
- Création et suivi de vérifications (checks) de services
- Génération de rapports PDF/PNG personnalisables via templates
- Envoi automatique d'emails avec rapports
- Dashboard avec statistiques et indicateurs de performance
- Gestion des utilisateurs avec rôles (admin/gestionnaire)

---

## Architecture technique

### Stack technique

- **Backend** : Laravel 12 (PHP 8.2+)
- **Frontend** : Blade Templates, Tailwind CSS, Alpine.js, Vite
- **Base de données** : MySQL 8.0
- **Containerisation** : Docker & Docker Compose
- **Serveur web** : PHP Built-in Server (production) / Apache/Nginx (recommandé)

### Architecture MVC

L'application suit le pattern Model-View-Controller (MVC) de Laravel :

- **Models** : `app/Models/` - Représentation des entités métier
- **Views** : `resources/views/` - Templates Blade pour l'interface
- **Controllers** : `app/Http/Controllers/` - Logique métier et gestion des requêtes

### Flux de données

```
Requête HTTP → Route → Middleware → Controller → Model → Base de données
                                                      ↓
                                              Vue (Blade) → Réponse HTML
```

---

## Technologies utilisées

### Backend

| Package | Version | Usage |
|---------|---------|-------|
| Laravel Framework | ^12.0 | Framework PHP principal |
| PHP | ^8.2 | Langage de programmation |
| DomPDF | ^3.1 | Génération de PDF |
| Intervention Image | ^2.7 | Traitement d'images |
| PHPSpreadsheet | ^4.4 | Manipulation Excel |
| Maatwebsite Excel | ^1.1 | Export/Import Excel |

### Frontend

| Package | Version | Usage |
|---------|---------|-------|
| Tailwind CSS | ^3.1.0 | Framework CSS |
| Alpine.js | ^3.4.2 | JavaScript réactif |
| Vite | ^6.2.4 | Build tool et HMR |
| Axios | ^1.8.2 | Requêtes HTTP |

### Infrastructure

- **Docker** : Containerisation de l'application
- **Docker Compose** : Orchestration multi-conteneurs
- **MySQL 8.0** : Base de données relationnelle

---

## Structure du projet

```
check-du-matin-blade/
├── app/
│   ├── Console/
│   │   ├── Commands/          # Commandes Artisan personnalisées
│   │   └── Kernel.php         # Planification des tâches
│   ├── Http/
│   │   ├── Controllers/       # Contrôleurs de l'application
│   │   ├── Middleware/        # Middlewares personnalisés
│   │   └── Requests/          # Form Requests (validation)
│   ├── Mail/                  # Classes d'emails
│   ├── Models/                # Modèles Eloquent
│   └── Providers/             # Service Providers
├── bootstrap/                 # Fichiers de démarrage Laravel
├── config/                    # Fichiers de configuration
├── database/
│   ├── migrations/            # Migrations de base de données
│   └── seeders/               # Seeders pour données de test
├── public/                    # Point d'entrée public
├── resources/
│   ├── css/                   # Styles CSS
│   ├── js/                    # JavaScript
│   └── views/                 # Templates Blade
├── routes/
│   ├── web.php               # Routes web
│   └── auth.php              # Routes d'authentification
├── storage/                   # Fichiers de stockage
├── tests/                     # Tests automatisés
├── docker-compose.yml         # Configuration Docker Compose
├── Dockerfile                 # Image Docker
└── .env                       # Variables d'environnement
```

---

## Base de données

### Schéma relationnel

```
users
├── id (PK)
├── name
├── email
├── password
├── role (admin|gestionnaire)
└── must_change_password

clients
├── id (PK)
├── label
├── logo
├── template_id (FK → templates)
└── check_time

client_user (pivot)
├── client_id (FK → clients)
└── user_id (FK → users)

templates
├── id (PK)
├── name
├── type (pdf|png|excel)
├── config (JSON)
└── excel_template

client_template (pivot)
├── client_id (FK → clients)
└── template_id (FK → templates)

categories
├── id (PK)
├── name
├── client_id (FK → clients)
└── parent_id (FK → categories, nullable)

services
├── id (PK)
├── title
├── category_id (FK → categories)
├── status (active|inactive)
└── created_by (FK → users)

checks
├── id (PK)
├── date_time
├── client_id (FK → clients)
├── statut (pending|in_progress|success|warning|error)
├── notes
├── created_by (FK → users)
└── email_sent_at

service_checks
├── id (PK)
├── check_id (FK → checks)
├── service_id (FK → services)
├── statut (pending|success|warning|error)
├── comment
└── intervenant

mailings
├── id (PK)
├── client_id (FK → clients)
├── email
└── type (sender|receiver|copie)

rappel_destinataires
├── id (PK)
├── client_id (FK → clients)
├── email
└── name

holidays
├── id (PK)
├── date
└── name
```

### Contraintes de clés étrangères

- **ON DELETE CASCADE** : Suppression en cascade pour les relations dépendantes
- **ON DELETE RESTRICT** : Empêche la suppression si des données liées existent

---

## Modèles et relations

### User

```php
Relations:
- clients() → belongsToMany(Client) via client_user
- checks() → hasMany(Check, 'created_by')
```

**Méthodes importantes :**
- `isAdmin()` : Vérifie si l'utilisateur est administrateur
- `isGestionnaire()` : Vérifie si l'utilisateur est gestionnaire

### Client

```php
Relations:
- users() → belongsToMany(User) via client_user
- categories() → hasMany(Category)
- services() → hasManyThrough(Service, Category)
- checks() → hasMany(Check)
- template() → belongsTo(Template)
- templates() → belongsToMany(Template) via client_template
- mailings() → hasMany(Mailing)
- destinataires() → hasMany(RappelDestinataire)
```

### Check

```php
Relations:
- client() → belongsTo(Client)
- creator() → belongsTo(User, 'created_by')
- serviceChecks() → hasMany(ServiceCheck)
```

**Statuts possibles :**
- `pending` : En attente
- `in_progress` : En cours
- `success` : Réussi
- `warning` : Avertissement
- `error` : Erreur

### Service

```php
Relations:
- category() → belongsTo(Category)
- creator() → belongsTo(User, 'created_by')
- checkServices() → hasMany(ServiceCheck)
```

### Category

```php
Relations:
- client() → belongsTo(Client)
- services() → hasMany(Service)
- parent() → belongsTo(Category, 'parent_id')
- children() → hasMany(Category, 'parent_id')
```

### Template

```php
Relations:
- clients() → belongsToMany(Client) via client_template
```

**Types de templates :**
- `pdf` : Génération de PDF
- `png` : Génération d'image PNG
- `excel` : Export Excel (désactivé pour import)

---

## Contrôleurs et routes

### Contrôleurs principaux

#### ClientController
- `index()` : Liste des clients (filtrée selon rôle)
- `create()` : Formulaire de création
- `store()` : Création d'un client (affectation auto pour gestionnaire)
- `show()` : Détails d'un client
- `edit()` : Formulaire d'édition
- `update()` : Mise à jour d'un client
- `destroy()` : Suppression d'un client (avec cascade)
- `duplicate()` : Duplication d'un client (affectation auto pour gestionnaire)
- `detach()` : Désaffectation d'un client (pour gestionnaire)
- `autoCheck()` : Création automatique de check

#### CheckController
- `index()` : Liste des checks (filtrée)
- `create()` : Formulaire de création
- `store()` : Création d'un check
- `show()` : Détails d'un check
- `export()` : Export PDF/PNG/Excel
- `send()` : Envoi d'email avec rapport
- `checkStatus()` : Vérification du statut d'un check

#### TemplateController
- `index()` : Liste des templates
- `create()` : Formulaire de création
- `store()` : Création d'un template
- `edit()` : Formulaire d'édition
- `update()` : Mise à jour d'un template
- `duplicate()` : Duplication d'un template
- `exportExample()` : Export d'un exemple

#### CategoryController, ServiceController, MailingController, etc.
- CRUD standard avec filtrage selon permissions

### Routes principales

```php
// Authentification
Route::middleware(['auth', 'force.password.change'])

// Clients
Route::resource('clients', ClientController::class);
Route::post('/clients/{client}/checks/auto', [ClientController::class, 'autoCheck']);
Route::get('/clients/{client}/duplicate', [ClientController::class, 'duplicate']);
Route::post('/clients/{client}/detach', [ClientController::class, 'detach']);

// Checks
Route::resource('checks', CheckController::class);
Route::get('/checks/{check}/export', [CheckController::class, 'export']);
Route::post('/checks/{check}/send', [CheckController::class, 'send']);

// Templates
Route::resource('templates', TemplateController::class);
Route::get('/templates/{template}/duplicate', [TemplateController::class, 'duplicate']);

// Utilisateurs (admin uniquement)
Route::resource('users', UserController::class);
```

---

## Fonctionnalités principales

### 1. Gestion des clients

- **Création/Édition** : Gestion complète des informations client
- **Logo** : Upload et stockage de logos (formats : jpeg, png, jpg, gif, webp, max 5MB)
- **Duplication** : Copie complète d'un client avec toutes ses données
- **Affectation automatique** : Les gestionnaires sont automatiquement affectés aux clients qu'ils créent/dupliquent
- **Suppression en cascade** : Suppression de toutes les données liées

### 2. Gestion des checks

- **Création manuelle** : Création de checks avec sélection de services
- **Création automatique** : Génération automatique basée sur `check_time` du client
- **Statuts** : Gestion du cycle de vie (pending → in_progress → success/warning/error)
- **Service Checks** : Vérification individuelle de chaque service
- **Notes** : Ajout de notes et commentaires
- **Intervenants** : Attribution d'intervenants aux service checks

### 3. Génération de rapports

- **Templates personnalisables** : Configuration JSON pour PDF/PNG
- **Export PDF** : Génération de PDF avec DomPDF
- **Export PNG** : Génération d'images PNG avec Intervention Image
- **Export Excel** : Export des données en format Excel
- **Configuration** : Couleurs, logos, mise en page personnalisables

### 4. Envoi d'emails

- **Rapports par email** : Envoi automatique de rapports PDF/PNG
- **Configuration SMTP** : Support SMTP avec authentification
- **Détection environnement** : Mode log en développement local
- **Gestion des erreurs** : Messages d'erreur détaillés pour le dépannage

### 5. Dashboard

- **Statistiques globales** : Vue d'ensemble des checks et services
- **Filtrage par rôle** : Admins voient tout, gestionnaires voient leurs clients
- **Graphiques** : Visualisation des données sur différentes périodes
- **Indicateurs** : Taux de réussite, checks en attente, etc.

### 6. Gestion des utilisateurs

- **Rôles** : Admin et Gestionnaire
- **Affectation clients** : Association gestionnaires ↔ clients
- **Changement de mot de passe** : Forcé à la première connexion
- **Isolation des données** : Gestionnaires voient uniquement leurs clients

---

## Système d'authentification et permissions

### Rôles

#### Administrateur (`admin`)
- Accès complet à tous les clients
- Gestion des utilisateurs
- Gestion des templates globaux
- Dashboard avec toutes les statistiques

#### Gestionnaire (`gestionnaire`)
- Accès uniquement aux clients assignés
- Gestion complète de ses clients (catégories, services, checks, etc.)
- Dashboard filtré sur ses clients
- Affectation automatique lors de création/duplication

### Middleware

- `auth` : Vérification de l'authentification
- `force.password.change` : Forcer le changement de mot de passe si requis

### Trait AuthorizeClientAccess

Utilisé dans les contrôleurs pour vérifier l'accès aux ressources :

```php
protected function authorizeClientAccess($client)
{
    $user = auth()->user();
    
    if ($user->isGestionnaire() && !$user->clients->contains($client)) {
        abort(403, 'Accès refusé à ce client.');
    }
}
```

### Filtrage automatique

Toutes les requêtes sont automatiquement filtrées selon le rôle :
- **Admin** : Pas de filtre
- **Gestionnaire** : Filtre sur `client_id IN (clients assignés)`

---

## Configuration et déploiement

### Variables d'environnement

```env
APP_NAME=Check du Matin
APP_ENV=production
APP_DEBUG=false
APP_URL=https://checking.c2s.fr

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=check_du_matin
DB_USERNAME=laravel
DB_PASSWORD=laravel

MAIL_MAILER=smtp
MAIL_HOST=relais.services.c-2-s.info
MAIL_PORT=25
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
```

### Docker Compose

Le fichier `docker-compose.yml` définit deux services :

1. **app** : Application Laravel
   - Port : 8001:8000
   - Volumes : `.env.prod`, `storage`, `wait-for-mysql.php`
   - Commandes : Migration, seeding, cache, scheduler

2. **db** : MySQL 8.0
   - Port : 3307:3306
   - Volume persistant : `db_data`

### Déploiement

#### 1. Prérequis
- Docker et Docker Compose installés
- Fichier `.env.prod` configuré

#### 2. Build et démarrage
```bash
docker compose build
docker compose up -d
```

#### 3. Initialisation
L'application exécute automatiquement :
- Génération de clé d'application
- Migrations de base de données
- Seeding des données initiales
- Création du lien symbolique storage
- Cache des configurations, routes et vues
- Démarrage du scheduler Laravel

#### 4. Vérification
```bash
docker compose logs app -f
docker compose ps
```

### Dockerfile

Le Dockerfile utilise un build multi-stage :

1. **Stage assets** : Build des assets frontend avec Node.js
2. **Stage app** : Installation PHP, extensions, Composer, copie des assets

---

## Maintenance et tâches planifiées

### Commandes Artisan personnalisées

#### DeleteOldChecks
```bash
php artisan checks:delete-old [--days=30]
```
Supprime les checks de plus de 30 jours (par défaut).

### Scheduler Laravel

Configuré dans `app/Console/Kernel.php` :

```php
$schedule->command('checks:delete-old')->dailyAt('02:00');
```

Le scheduler s'exécute via `php artisan schedule:work` dans le conteneur Docker.

### Logs

Les logs sont disponibles dans :
- `storage/logs/laravel.log` : Logs applicatifs
- `docker compose logs app` : Logs du conteneur

---

## Sécurité

### Authentification

- Hashage des mots de passe avec bcrypt
- Protection CSRF sur tous les formulaires
- Middleware d'authentification sur toutes les routes protégées

### Autorisations

- Vérification systématique des permissions d'accès
- Isolation des données par rôle
- Protection contre l'accès non autorisé aux ressources

### Validation

- Validation des données d'entrée via Form Requests
- Sanitization des données utilisateur
- Validation des types de fichiers uploadés

### Stockage

- Logos stockés dans `storage/app/public/logos/`
- Permissions de fichiers : 775
- Lien symbolique public pour l'accès web

### URLs

- Utilisation de chemins relatifs pour éviter les problèmes DNS
- Redirections avec chemins relatifs (`/clients` au lieu de `route()`)

---

## Dépannage

### Erreur 500 lors de la création de client

**Symptômes** : Erreur 500 après soumission du formulaire de création

**Solutions** :
1. Vérifier les logs : `docker compose logs app | tail -50`
2. Vérifier les permissions de stockage : `chmod -R 775 storage`
3. Vérifier la configuration de la base de données
4. Vérifier les contraintes de clés étrangères

### Erreur DNS lors de la suppression

**Symptômes** : Erreur 404 sur les URLs absolues

**Solution** : Utiliser des chemins relatifs dans les redirections

### Logos qui disparaissent après duplication

**Symptômes** : Logo dupliqué partagé entre clients

**Solution** : Copie physique du fichier logo lors de la duplication (implémenté)

### Erreur SMTP Connection Refused

**Symptômes** : Impossible d'envoyer des emails

**Solutions** :
1. Vérifier la configuration SMTP dans `.env`
2. Vérifier l'accessibilité du serveur SMTP depuis le conteneur
3. En développement local, utiliser `MAIL_MAILER=log`
4. Vérifier les règles de firewall

### MySQL non accessible

**Symptômes** : Erreur de connexion à la base de données

**Solutions** :
1. Vérifier que le conteneur `db` est démarré : `docker compose ps`
2. Vérifier les variables d'environnement `DB_*`
3. Vérifier le healthcheck MySQL : `docker compose logs db`

### Assets non chargés

**Symptômes** : CSS/JS non chargés

**Solutions** :
1. Rebuild des assets : `npm run build`
2. Vérifier le lien symbolique : `php artisan storage:link`
3. Vérifier les permissions de `public/build`

---

## Commandes utiles

### Développement

```bash
# Démarrer l'environnement
docker compose up -d

# Voir les logs
docker compose logs app -f

# Accéder au conteneur
docker compose exec app bash

# Exécuter des commandes Artisan
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed

# Rebuild
docker compose build --no-cache
docker compose up -d
```

### Maintenance

```bash
# Supprimer les anciens checks
docker compose exec app php artisan checks:delete-old

# Vider le cache
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan view:clear

# Optimiser
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

### Base de données

```bash
# Backup
docker compose exec db mysqldump -u laravel -plaravel check_du_matin > backup.sql

# Restore
docker compose exec -T db mysql -u laravel -plaravel check_du_matin < backup.sql
```

---

## Évolutions futures

### Fonctionnalités possibles

- **API REST** : Endpoints pour applications mobiles
- **Notifications en temps réel** : WebSockets pour les alertes
- **Authentification à deux facteurs** : 2FA pour renforcer la sécurité
- **Audit trail** : Historique complet des actions utilisateurs
- **Rapports avancés** : Graphiques et analyses approfondies
- **Import Excel** : Réactivation de l'import Excel pour templates
- **Multi-langues** : Support de plusieurs langues

### Améliorations techniques

- **Cache Redis** : Amélioration des performances
- **Queue workers** : Traitement asynchrone des emails
- **Tests automatisés** : Augmentation de la couverture de tests
- **CI/CD** : Pipeline de déploiement automatique
- **Monitoring** : Intégration d'outils de monitoring (Sentry, etc.)

---

## Support et contribution

Pour toute question ou problème, consulter :
- Les logs applicatifs : `storage/logs/laravel.log`
- Les logs Docker : `docker compose logs`
- La documentation Laravel : https://laravel.com/docs

---

**Version** : 1.0  
**Dernière mise à jour** : 2025-01-XX  
**Auteur** : Équipe Check du Matin

