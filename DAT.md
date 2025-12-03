# Document d'Architecture Technique (DAT) - Check du Matin

## 📋 Table des matières

1. [Introduction](#introduction)
2. [Architecture globale](#architecture-globale)
3. [Architecture applicative](#architecture-applicative)
4. [Architecture de données](#architecture-de-données)
5. [Architecture technique détaillée](#architecture-technique-détaillée)
6. [Sécurité](#sécurité)
7. [Performance et scalabilité](#performance-et-scalabilité)
8. [Intégrations](#intégrations)
9. [Déploiement](#déploiement)
10. [Évolutivité](#évolutivité)

---

## Introduction

### Objet du document

Ce Document d'Architecture Technique (DAT) décrit l'architecture complète de l'application **Check du Matin**, destinée aux développeurs et architectes techniques.

### Contexte

Check du Matin est une application web Laravel permettant la gestion de vérifications de services pour plusieurs clients, avec génération de rapports et envoi d'emails automatiques.

### Périmètre

- Architecture applicative (backend/frontend)
- Architecture de données
- Architecture d'infrastructure
- Sécurité et performances

---

## Architecture globale

### Vue d'ensemble

```
┌─────────────────────────────────────────────────────────────┐
│                    Couche Présentation                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │   Browser    │  │   Mobile      │  │   API Client  │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              Couche Application (Laravel)                   │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │ Controllers  │  │  Middleware   │  │   Services   │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │   Models     │  │   Policies   │  │   Jobs/Queue │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              Couche Données                                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │    MySQL     │  │   Storage    │  │     Cache    │     │
│  │   (8.0)      │  │   (Files)    │  │   (Files)    │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└─────────────────────────────────────────────────────────────┘
```

### Composants principaux

1. **Frontend** : Blade Templates + Tailwind CSS + Alpine.js
2. **Backend** : Laravel 12 (PHP 8.2)
3. **Base de données** : MySQL 8.0
4. **Containerisation** : Docker + Docker Compose
5. **Génération de documents** : DomPDF, Intervention Image
6. **Email** : SMTP (SwiftMailer)

---

## Architecture applicative

### Pattern MVC

L'application suit strictement le pattern **Model-View-Controller** de Laravel :

#### Models (`app/Models/`)

Représentent les entités métier et leurs relations :

```php
// Exemple : Client Model
class Client extends Model
{
    // Relations
    public function users()           // Many-to-Many
    public function categories()      // One-to-Many
    public function checks()          // One-to-Many
    public function template()        // Many-to-One
    public function mailings()        // One-to-Many
}
```

**Principes** :
- Utilisation d'Eloquent ORM
- Relations définies via méthodes
- Scopes pour requêtes réutilisables
- Accessors/Mutators pour transformation de données

#### Views (`resources/views/`)

Templates Blade organisés par fonctionnalité :

```
resources/views/
├── layouts/
│   └── app.blade.php          # Layout principal
├── components/                 # Composants réutilisables
├── clients/                    # Vues clients
├── checks/                     # Vues checks
├── templates/                  # Vues templates
└── dashboard.blade.php         # Dashboard
```

**Principes** :
- Composants réutilisables
- Layouts partagés
- Sections et stacks Blade
- Inclusion conditionnelle selon permissions

#### Controllers (`app/Http/Controllers/`)

Gèrent la logique métier et orchestrent les opérations :

```php
class ClientController extends Controller
{
    use AuthorizesClientAccess;  // Trait pour permissions
    
    public function index()       // Liste
    public function create()     // Formulaire création
    public function store()      // Création
    public function show()       // Détails
    public function edit()       // Formulaire édition
    public function update()     // Mise à jour
    public function destroy()    // Suppression
    public function duplicate()  // Duplication
}
```

**Principes** :
- Single Responsibility
- Utilisation de traits pour code partagé
- Validation via Form Requests
- Transactions DB pour opérations critiques

### Middleware

#### Middleware d'authentification

```php
Route::middleware(['auth', 'force.password.change'])
```

- `auth` : Vérifie l'authentification
- `force.password.change` : Force le changement de mot de passe si requis

#### Middleware personnalisés

- `AuthorizeClientAccess` : Vérifie l'accès aux ressources client
- Filtrage automatique selon le rôle utilisateur

### Services et Traits

#### Trait `AuthorizesClientAccess`

```php
trait AuthorizesClientAccess
{
    protected function authorizeClientAccess($client)
    {
        $user = auth()->user();
        if ($user->isGestionnaire() && !$user->clients->contains($client)) {
            abort(403, 'Accès refusé à ce client.');
        }
    }
    
    protected function getAccessibleClients()
    {
        $user = auth()->user();
        return $user->isAdmin() 
            ? Client::all() 
            : $user->clients;
    }
}
```

**Usage** : Utilisé dans tous les contrôleurs pour sécuriser l'accès.

### Gestion des permissions

#### Système de rôles

```php
// User Model
public function isAdmin(): bool
{
    return $this->role === 'admin';
}

public function isGestionnaire(): bool
{
    return $this->role === 'gestionnaire';
}
```

#### Filtrage automatique

Toutes les requêtes sont filtrées selon le rôle :

```php
// Dans les contrôleurs
if ($user->isGestionnaire()) {
    $clients = $user->clients;  // Filtrage automatique
} else {
    $clients = Client::all();    // Admin voit tout
}
```

---

## Architecture de données

### Modèle relationnel

```
┌─────────────┐
│    users    │
└──────┬──────┘
       │
       │ N─┐
       │   │ client_user (pivot)
       │ ┌─┘
       │ │
┌──────▼─┴──────┐         ┌─────────────┐
│   clients     │────────▶│  templates  │
└───┬───────────┘    N:M  └─────────────┘
    │
    ├──▶ categories (1:N)
    │       └──▶ services (1:N)
    │
    ├──▶ checks (1:N)
    │       └──▶ service_checks (1:N)
    │
    ├──▶ mailings (1:N)
    │
    └──▶ rappel_destinataires (1:N)
```

### Tables principales

#### users
```sql
- id (PK, BIGINT)
- name (VARCHAR 255)
- email (VARCHAR 255, UNIQUE)
- password (VARCHAR 255, HASHED)
- role (ENUM: 'admin', 'gestionnaire')
- must_change_password (BOOLEAN)
- timestamps
```

#### clients
```sql
- id (PK, BIGINT)
- label (VARCHAR 255)
- logo (VARCHAR 255, nullable)
- template_id (FK → templates, nullable)
- check_time (TIME)
- timestamps
```

#### checks
```sql
- id (PK, BIGINT)
- date_time (DATETIME)
- client_id (FK → clients, ON DELETE CASCADE)
- statut (ENUM: pending, in_progress, success, warning, error)
- notes (TEXT, nullable)
- created_by (FK → users)
- email_sent_at (DATETIME, nullable)
- timestamps
```

#### service_checks
```sql
- id (PK, BIGINT)
- check_id (FK → checks, ON DELETE CASCADE)
- service_id (FK → services, ON DELETE CASCADE)
- statut (ENUM: pending, success, warning, error)
- comment (TEXT, nullable)
- intervenant (VARCHAR 255, nullable)
- timestamps
```

### Contraintes d'intégrité

#### Clés étrangères

```sql
-- Cascade pour dépendances fortes
ALTER TABLE checks 
ADD CONSTRAINT fk_checks_client 
FOREIGN KEY (client_id) REFERENCES clients(id) 
ON DELETE CASCADE;

-- Restrict pour dépendances critiques
ALTER TABLE clients 
ADD CONSTRAINT fk_clients_template 
FOREIGN KEY (template_id) REFERENCES templates(id) 
ON DELETE RESTRICT;
```

#### Index

```sql
-- Index pour performances
CREATE INDEX idx_checks_client_id ON checks(client_id);
CREATE INDEX idx_checks_date_time ON checks(date_time);
CREATE INDEX idx_service_checks_check_id ON service_checks(check_id);
CREATE INDEX idx_client_user_user_id ON client_user(user_id);
```

### Stratégie de sauvegarde

- **Sauvegardes quotidiennes** : mysqldump automatique
- **Rétention** : 30 jours
- **Stockage** : Volume Docker persistant + backup externe

---

## Architecture technique détaillée

### Stack technique

#### Backend

| Composant | Version | Rôle |
|-----------|---------|------|
| PHP | 8.2+ | Langage de programmation |
| Laravel | 12.0 | Framework MVC |
| Composer | 2.x | Gestionnaire de dépendances |

#### Frontend

| Composant | Version | Rôle |
|-----------|---------|------|
| Blade | 12.0 | Moteur de templates |
| Tailwind CSS | 3.1.0 | Framework CSS |
| Alpine.js | 3.4.2 | JavaScript réactif |
| Vite | 6.2.4 | Build tool |

#### Bibliothèques

| Bibliothèque | Version | Usage |
|--------------|---------|-------|
| DomPDF | 3.1 | Génération PDF |
| Intervention Image | 2.7 | Traitement images |
| PHPSpreadsheet | 4.4 | Manipulation Excel |
| Maatwebsite Excel | 1.1 | Export Excel |

### Structure des dossiers

```
app/
├── Console/
│   ├── Commands/           # Commandes Artisan
│   │   ├── DeleteOldChecks.php
│   │   └── CreateChecks.php
│   └── Kernel.php          # Scheduler
├── Http/
│   ├── Controllers/        # Contrôleurs
│   │   ├── Auth/          # Authentification
│   │   ├── ClientController.php
│   │   ├── CheckController.php
│   │   └── ...
│   ├── Middleware/         # Middlewares
│   ├── Requests/           # Form Requests
│   └── Traits/             # Traits réutilisables
├── Mail/                   # Classes d'emails
├── Models/                 # Modèles Eloquent
└── Providers/              # Service Providers

resources/
├── css/                    # Styles CSS
├── js/                     # JavaScript
└── views/                  # Templates Blade

routes/
├── web.php                 # Routes web
└── auth.php                # Routes auth

database/
├── migrations/             # Migrations DB
└── seeders/                # Seeders

config/                     # Fichiers de configuration
storage/                    # Fichiers générés
public/                     # Point d'entrée public
```

### Flux de traitement

#### Création d'un check

```
1. User → POST /checks
2. CheckController::store()
   ├── Validation (Form Request)
   ├── authorizeClientAccess()
   ├── DB::transaction()
   │   ├── Check::create()
   │   └── ServiceCheck::create() (pour chaque service)
   └── Response
```

#### Génération d'un rapport PDF

```
1. User → GET /checks/{id}/export?format=pdf
2. CheckController::export()
   ├── Chargement du check + relations
   ├── Chargement du template
   ├── DomPDF::loadHtml()
   ├── Génération PDF
   └── Response (download)
```

#### Envoi d'email

```
1. User → POST /checks/{id}/send
2. CheckController::send()
   ├── Génération du rapport (PDF/PNG)
   ├── Récupération des destinataires
   ├── Mail::send()
   │   ├── SMTP Connection
   │   ├── Attachment
   │   └── Send
   ├── Log::info() (succès)
   └── Response
```

### Gestion des erreurs

#### Logging

```php
// Configuration dans config/logging.php
'channels' => [
    'single' => [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
    ],
]
```

#### Gestion des exceptions

```php
try {
    // Opération critique
} catch (\Exception $e) {
    \Log::error('Erreur: ' . $e->getMessage(), [
        'context' => [...],
        'exception' => $e,
    ]);
    // Gestion de l'erreur
}
```

---

## Sécurité

### Authentification

- **Hashage** : bcrypt (10 rounds)
- **Sessions** : Laravel sessions (fichiers)
- **CSRF** : Protection sur tous les formulaires
- **XSS** : Échappement automatique dans Blade

### Autorisations

#### Contrôle d'accès

```php
// Vérification systématique
protected function authorizeClientAccess($client)
{
    if ($user->isGestionnaire() && !$user->clients->contains($client)) {
        abort(403);
    }
}
```

#### Isolation des données

- Filtrage automatique selon le rôle
- Pas de fuite de données entre clients
- Validation des IDs dans les requêtes

### Validation

#### Form Requests

```php
class StoreClientRequest extends FormRequest
{
    public function rules()
    {
        return [
            'label' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'template_id' => 'required|exists:templates,id',
        ];
    }
}
```

### Stockage sécurisé

- Logos : Validation du type et de la taille
- Permissions : 775 pour storage
- Lien symbolique : `public/storage` → `storage/app/public`

### Configuration

- `.env.prod` : Jamais commité dans Git
- `APP_DEBUG=false` en production
- `APP_ENV=production` en production
- Mots de passe : Changement forcé à la première connexion

---

## Performance et scalabilité

### Optimisations actuelles

#### Cache

```php
// Cache de configuration
php artisan config:cache

// Cache des routes
php artisan route:cache

// Cache des vues
php artisan view:cache
```

#### Requêtes optimisées

```php
// Eager loading pour éviter N+1
$clients = Client::with(['categories.services', 'checks'])->get();

// Index sur colonnes fréquemment utilisées
CREATE INDEX idx_checks_client_date ON checks(client_id, date_time);
```

### Limitations actuelles

- **Serveur unique** : Pas de load balancing
- **Cache fichier** : Pas de Redis/Memcached
- **Queue synchrone** : Pas de workers asynchrones
- **Base de données unique** : Pas de réplication

### Améliorations possibles

#### Court terme

1. **Redis pour cache** : Amélioration des performances
2. **Queue workers** : Traitement asynchrone des emails
3. **CDN** : Pour les assets statiques

#### Long terme

1. **Load balancing** : Plusieurs instances
2. **Réplication DB** : Master-slave
3. **Microservices** : Séparation des services

---

## Intégrations

### SMTP

```php
// Configuration dans .env
MAIL_MAILER=smtp
MAIL_HOST=relais.services.c-2-s.info
MAIL_PORT=25
```

**Gestion des erreurs** :
- Détection de l'environnement (local/production)
- Messages d'erreur explicites
- Fallback sur log en développement

### Génération de documents

#### PDF (DomPDF)

```php
$pdf = \PDF::loadHtml($html);
return $pdf->download('rapport.pdf');
```

#### PNG (Intervention Image)

```php
$img = Image::make($html);
$img->save($path);
```

### Scheduler Laravel

```php
// app/Console/Kernel.php
$schedule->command('checks:create')->everyFiveMinutes();
$schedule->command('checks:delete-old')->dailyAt('02:00');
```

Exécution via `php artisan schedule:work` dans Docker.

---

## Déploiement

### Architecture Docker

```
┌─────────────────────────────────────┐
│         Docker Compose               │
│  ┌──────────────┐  ┌──────────────┐ │
│  │     app      │  │      db       │ │
│  │  (Laravel)   │  │   (MySQL 8)   │ │
│  │  Port 8001   │  │  Port 3307   │ │
│  └──────────────┘  └──────────────┘ │
└─────────────────────────────────────┘
```

### Build process

1. **Stage 1 (Assets)** : Build frontend avec Node.js
2. **Stage 2 (App)** : Build PHP + copie assets
3. **Runtime** : Exécution avec PHP built-in server

### Variables d'environnement

Gérées via `.env.prod` monté dans le conteneur.

### Volumes persistants

- `app_storage` : Fichiers de l'application
- `db_data` : Données MySQL

---

## Évolutivité

### Extensibilité

#### Ajout de nouvelles fonctionnalités

1. **Nouveau modèle** : Créer migration + Model
2. **Nouveau contrôleur** : Créer Controller + Routes
3. **Nouvelles vues** : Créer templates Blade
4. **Permissions** : Ajouter dans `AuthorizesClientAccess`

#### Ajout de nouveaux rôles

1. Modifier `User` model : Ajouter méthode `isNouveauRole()`
2. Modifier middleware : Ajouter logique de filtrage
3. Modifier vues : Ajouter conditions selon rôle

### Maintenance

#### Commandes Artisan

Facilement extensible via `app/Console/Commands/`.

#### Scheduler

Configuration centralisée dans `app/Console/Kernel.php`.

---

**Version** : 1.0  
**Dernière mise à jour** : 2025-01-XX  
**Auteur** : Équipe Check du Matin

