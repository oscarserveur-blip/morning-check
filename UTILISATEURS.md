# Système de Gestion des Utilisateurs - Check du Matin

## Vue d'ensemble

Le système de gestion des utilisateurs permet d'affecter des utilisateurs aux clients et de contrôler leurs accès selon leurs rôles. **Chaque utilisateur gestionnaire a un contrôle complet sur ses clients assignés**, incluant la gestion des catégories, services, checks, mailings, destinataires et templates.

## Rôles Utilisateurs

### 1. Administrateur (`admin`)
- **Accès complet** : Peut voir et gérer tous les clients
- **Gestion des utilisateurs** : Peut créer, modifier et supprimer des utilisateurs
- **Gestion des templates** : Accès complet aux templates de tous les clients
- **Dashboard global** : Voit les statistiques de tous les clients
- **Gestion complète** : Accès complet à tous les clients, catégories, services, checks, mailings, destinataires

### 2. Gestionnaire (`gestionnaire`)
- **Accès limité** : Ne peut voir que les clients qui lui sont assignés
- **Gestion complète de ses clients** : Contrôle total sur ses clients assignés
- **Dashboard filtré** : Voit uniquement les statistiques de ses clients
- **Pas de gestion des utilisateurs** : Ne peut pas créer d'autres utilisateurs
- **Gestion des templates** : Peut gérer les templates de ses clients assignés
- **Gestion complète** : Catégories, services, checks, mailings, destinataires de ses clients

## 🔒 **Gestion Complète par Client**

### **Ce qu'un Gestionnaire peut faire sur ses clients :**

#### ✅ **Clients**
- Voir la liste de ses clients assignés
- Accéder aux détails complets de chaque client
- Voir les statistiques de ses clients

#### ✅ **Catégories**
- Créer, modifier, supprimer des catégories pour ses clients
- Organiser la hiérarchie des catégories
- Gérer les catégories parentes/enfantes

#### ✅ **Services**
- Créer, modifier, supprimer des services dans les catégories de ses clients
- Activer/désactiver des services
- Organiser les services par catégorie

#### ✅ **Checks**
- Créer, modifier, supprimer des checks pour ses clients
- Gérer le statut des checks (pending, in_progress, completed, failed)
- Ajouter des notes et commentaires
- Exporter les checks en PDF/Excel

#### ✅ **Templates**
- Créer, modifier, supprimer des templates pour ses clients
- Dupliquer des templates existants
- Associer des templates à ses clients
- Gérer les configurations de templates

#### ✅ **Mailings**
- Créer, modifier, supprimer des mailings pour ses clients
- Gérer les types de mailings (sender, receiver, copie)
- Organiser la liste des emails de contact

#### ✅ **Destinataires de Rappel**
- Créer, modifier, supprimer des destinataires de rappel
- Gérer les listes de diffusion
- Organiser les contacts pour les notifications

## Sécurité et Permissions

### 🔒 **Isolation Complète par Client**
- **Chaque gestionnaire** ne voit que ses clients assignés
- **Toutes les ressources** sont automatiquement filtrées selon les permissions
- **Pas de fuite** d'informations entre clients
- **Contrôle total** sur ses ressources assignées

### 🔒 **Dashboard Adaptatif**
- **Administrateurs** : Voir toutes les statistiques globales
- **Gestionnaires** : Voir uniquement les statistiques de leurs clients assignés
- **Filtrage automatique** : Toutes les données sont filtrées selon les permissions

### 🔒 **Vérifications Systématiques**
- **CRUD sécurisé** : Toutes les opérations vérifient les permissions
- **Validation des clients** : Vérification que l'utilisateur a accès aux clients sélectionnés
- **Isolation des données** : Pas de fuite entre utilisateurs ou clients
- **Accès refusé** automatique aux ressources non autorisées

## Architecture Technique

### Modèles et Relations

#### User Model
```php
// Relations
public function clients() // Many-to-Many avec Client via client_user
public function checks()  // One-to-Many avec Check (created_by)

// Méthodes helper
public function isAdmin()
public function isGestionnaire()
public function canAccessClient($clientId)
public function getAccessibleClients()
```

#### Client Model
```php
// Relations
public function users() // Many-to-Many avec User via client_user
public function templates() // Many-to-Many avec Template via client_template
public function categories() // One-to-Many avec Category
public function services() // HasManyThrough avec Service via Category
public function checks() // One-to-Many avec Check
public function mailings() // One-to-Many avec Mailing
public function destinataires() // One-to-Many avec RappelDestinataire
public function rappelDestinataires() // Alias pour destinataires
```

#### Template Model
```php
// Relations
public function clients() // Many-to-Many avec Client via client_template
```

### Tables Pivot
```sql
-- client_user
- id
- user_id (foreign key vers users)
- client_id (foreign key vers clients)
- timestamps

-- client_template
- id
- client_id (foreign key vers clients)
- template_id (foreign key vers templates)
- timestamps
```

### Trait ManagesUserPermissions

Centralise la logique de permissions avec des méthodes spécialisées :
- `filterClientsByUserPermissions()` - Filtre les clients selon les permissions
- `filterCategoriesByUserPermissions()` - Filtre les catégories selon les permissions
- `filterServicesByUserPermissions()` - Filtre les services selon les permissions
- `filterChecksByUserPermissions()` - Filtre les checks selon les permissions
- `filterMailingsByUserPermissions()` - Filtre les mailings selon les permissions
- `filterDestinatairesByUserPermissions()` - Filtre les destinataires selon les permissions
- `authorizeClientAccess()` - Vérifie l'accès à un client spécifique
- `authorizeResourceAccess()` - Vérifie l'accès à une ressource liée à un client
- `getAccessibleClients()` - Récupère les clients accessibles

## Interface Utilisateur

### Gestion des Utilisateurs (`/users`)
- **Liste des utilisateurs** : Affichage avec rôles et clients assignés
- **Création d'utilisateur** : Interface moderne avec sélection de rôle et clients
- **Édition d'utilisateur** : Modification des informations et réassignation
- **Détails utilisateur** : Statistiques et historique des actions

### Gestion des Clients (`/clients`)
- **Liste des clients** : Filtrage automatique selon les permissions
- **Détails client** : Vue complète avec onglets pour toutes les ressources
- **Onglets disponibles** : Catégories, Services, Checks, Mailings, Destinataires

### Gestion des Catégories (`/categories`)
- **Liste des catégories** : Filtrage automatique selon les clients assignés
- **Création/édition** : Interface intuitive avec sélection du client
- **Hiérarchie** : Gestion des catégories parentes/enfantes

### Gestion des Services (`/services`)
- **Liste des services** : Filtrage automatique selon les catégories des clients assignés
- **Création/édition** : Sélection automatique des catégories autorisées
- **Statut** : Activation/désactivation des services

### Gestion des Checks (`/checks`)
- **Liste des checks** : Filtrage automatique selon les clients assignés
- **Création/édition** : Sélection automatique des clients autorisés
- **Statuts** : Gestion complète du cycle de vie des checks
- **Export** : PDF et Excel avec données filtrées

### Gestion des Templates (`/templates`)
- **Liste des templates** : Filtrage automatique selon les clients assignés
- **Création/édition** : Sélection des clients qui utiliseront le template
- **Duplication** : Copie avec associations clients
- **Suppression** : Sécurisée selon les permissions

### Gestion des Mailings (`/mailings`)
- **Liste des mailings** : Filtrage automatique selon les clients assignés
- **Création/édition** : Sélection automatique des clients autorisés
- **Types** : Sender, Receiver, Copie

### Gestion des Destinataires (`/rappel-destinataires`)
- **Liste des destinataires** : Filtrage automatique selon les clients assignés
- **Création/édition** : Sélection automatique des clients autorisés
- **Types** : Sender, Receiver, Copie

## Sécurité et Permissions

### Middleware
- `RoleMiddleware` : Contrôle l'accès basé sur les rôles
- Vérification automatique des permissions client pour les gestionnaires

### Contrôleurs Sécurisés
- **DashboardController** : Données filtrées selon le rôle
- **ClientController** : Accès restreint aux clients assignés
- **UserController** : Gestion complète (admin uniquement)
- **TemplateController** : ✅ **ACCÈS PAR CLIENT** (selon les permissions)
- **CategoryController** : Filtrage automatique selon les clients
- **ServiceController** : Filtrage automatique selon les clients
- **CheckController** : ✅ **FILTRAGE AUTOMATIQUE** selon les permissions
- **MailingController** : ✅ **FILTRAGE AUTOMATIQUE** selon les permissions
- **RappelDestinataireController** : ✅ **FILTRAGE AUTOMATIQUE** selon les permissions

### Vérifications Automatiques
- Dashboard adapté au rôle (admin vs gestionnaire)
- Liste des clients filtrée automatiquement
- Interdiction d'accès aux clients non-assignés
- **Templates isolés par client** - Pas de fuite entre clients
- **Toutes les ressources filtrées** selon les permissions
- Vérification des permissions à chaque action CRUD

## Utilisation

### Workflow de Gestionnaire
1. **Se connecte** avec son compte gestionnaire
2. **Voit uniquement ses clients** dans le dashboard
3. **Gère complètement ses clients** :
   - Crée/modifie des catégories
   - Gère les services
   - Crée et suit les checks
   - Configure les templates
   - Organise les mailings
   - Gère les destinataires
4. **Accès refusé** aux autres clients et ressources

### Créer une Structure Complète
1. **Créer des catégories** pour organiser les services
2. **Ajouter des services** dans les catégories
3. **Configurer des templates** pour les rapports
4. **Créer des checks** pour vérifier les services
5. **Organiser les mailings** et destinataires
6. **Suivre les performances** via le dashboard

## Seeders et Tests

### UserSeeder
Crée automatiquement :
- 1 Administrateur : `admin@checkdumatin.com`
- 2 Gestionnaires : `gestionnaire1@checkdumatin.com`, `gestionnaire2@checkdumatin.com`
- Assignation automatique des clients existants

### Comptes de Test
Tous avec le mot de passe : `password`

## Migration et Déploiement

### Commandes à exécuter
```bash
php artisan migrate
php artisan db:seed --class=UserSeeder
```

### Structure des Permissions
- Les permissions sont vérifiées à chaque requête
- Utilisation des relations Eloquent pour optimiser les performances
- Cache des permissions dans les sessions utilisateur

## Évolutions Futures

### Fonctionnalités Possibles
- **Notifications** : Alertes pour les gestionnaires sur leurs clients
- **Rapports** : Génération de rapports par gestionnaire
- **Historique** : Suivi des actions par utilisateur
- **API** : Endpoints pour applications mobiles
- **Rôles avancés** : Permissions granulaires par fonctionnalité

### Améliorations Techniques
- Cache des permissions utilisateur
- Audit trail des actions
- Authentification à deux facteurs
- Single Sign-On (SSO)

## ✅ **Points de Sécurité Implémentés**

### 1. Gestion Complète par Client
- **Contrôle total** : Chaque gestionnaire gère complètement ses clients
- **Isolation parfaite** : Pas de fuite d'informations entre clients
- **Sécurité renforcée** : Chaque gestionnaire est responsable de ses ressources

### 2. Filtrage Automatique Systématique
- **Toutes les ressources** sont automatiquement filtrées selon les permissions
- **Interface adaptée** : Chaque utilisateur ne voit que ses données
- **Sécurité proactive** : Filtrage au niveau de la base de données

### 3. Vérifications de Permissions
- **CRUD sécurisé** : Toutes les opérations vérifient les permissions
- **Validation systématique** : Vérification des accès à chaque action
- **Gestion des erreurs** : Messages d'erreur clairs et sécurisés

### 4. Dashboard et Statistiques
- **Données filtrées** : Les gestionnaires ne voient que leurs statistiques
- **Interface adaptée** : Dashboard personnalisé selon le rôle
- **Performance optimisée** : Requêtes optimisées avec relations Eloquent 