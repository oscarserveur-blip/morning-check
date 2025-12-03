# Guide d'Utilisation Technique - Check du Matin

## 📋 Table des matières

1. [Introduction](#introduction)
2. [Présentation de l'application](#présentation-de-lapplication)
3. [Installation et démarrage](#installation-et-démarrage)
4. [Configuration de base](#configuration-de-base)
5. [Opérations courantes](#opérations-courantes)
6. [Maintenance quotidienne](#maintenance-quotidienne)
7. [Résolution de problèmes courants](#résolution-de-problèmes-courants)
8. [Sauvegarde et restauration](#sauvegarde-et-restauration)
9. [Mise à jour de l'application](#mise-à-jour-de-lapplication)
10. [Annexes](#annexes)

---

## Introduction

### À qui s'adresse ce guide ?

Ce guide est destiné aux **personnes non-techniques** qui doivent **maintenir et faire fonctionner** l'application Check du Matin au quotidien. Vous n'avez pas besoin d'être développeur pour suivre ce guide.

### Objectifs

Après avoir lu ce guide, vous serez capable de :
- ✅ Démarrer et arrêter l'application
- ✅ Effectuer des sauvegardes
- ✅ Résoudre les problèmes courants
- ✅ Mettre à jour l'application
- ✅ Comprendre la structure de base

### Prérequis

- Accès SSH au serveur
- Connaissances de base en ligne de commande Linux
- Accès au répertoire de l'application

---

## Présentation de l'application

### Qu'est-ce que Check du Matin ?

Check du Matin est une **application web** qui permet de :
- Gérer plusieurs clients
- Créer des vérifications (checks) de services
- Générer des rapports PDF/PNG
- Envoyer des emails automatiques
- Suivre les statistiques via un dashboard

### Architecture simplifiée

L'application fonctionne avec **Docker**, ce qui signifie qu'elle tourne dans des "conteneurs" isolés :

```
┌─────────────────────────────────────┐
│   Application Web (Port 8001)       │
│   - Interface utilisateur           │
│   - Logique métier                  │
└─────────────────────────────────────┘
              │
              ├──► Base de données MySQL
              │    (Port 3307)
              │
              └──► Stockage des fichiers
                   (logos, rapports)
```

### Composants principaux

1. **Application Web** : L'interface que les utilisateurs voient
2. **Base de données** : Où sont stockées toutes les données
3. **Stockage** : Où sont stockés les logos et fichiers

---

## Installation et démarrage

### Première installation

#### Étape 1 : Vérifier que Docker est installé

```bash
docker --version
docker compose version
```

Si ces commandes ne fonctionnent pas, Docker n'est pas installé. Contactez votre administrateur système.

#### Étape 2 : Se placer dans le répertoire de l'application

```bash
cd /chemin/vers/check-du-matin-blade
```

**Exemple** : Si l'application est dans `/home/user/morning-check` :
```bash
cd /home/user/morning-check
```

#### Étape 3 : Vérifier le fichier de configuration

Assurez-vous que le fichier `.env.prod` existe et contient les bonnes informations :

```bash
ls -la .env.prod
```

Si le fichier n'existe pas, créez-le en copiant `.env.example` :
```bash
cp .env.example .env.prod
```

Puis éditez-le avec vos paramètres (voir section Configuration).

#### Étape 4 : Construire et démarrer l'application

```bash
# Construire l'image Docker
docker compose build

# Démarrer les services
docker compose up -d
```

Le `-d` signifie "détaché", l'application tourne en arrière-plan.

#### Étape 5 : Vérifier que tout fonctionne

```bash
# Voir les logs
docker compose logs app

# Vérifier l'état des conteneurs
docker compose ps
```

Vous devriez voir deux conteneurs :
- `check-du-matin-blade-app` : Statut "Up"
- `check-du-matin-blade-db` : Statut "Up (healthy)"

#### Étape 6 : Accéder à l'application

Ouvrez votre navigateur et allez à :
- **URL locale** : `http://localhost:8001`
- **URL production** : `https://checking.c2s.fr` (selon votre configuration)

### Démarrage quotidien

Si l'application est déjà installée, pour la démarrer :

```bash
cd /chemin/vers/check-du-matin-blade
docker compose up -d
```

**Temps de démarrage** : Environ 30 secondes à 1 minute.

### Arrêt de l'application

```bash
docker compose down
```

**Attention** : Cela arrête l'application mais **ne supprime pas les données**.

Pour arrêter ET supprimer les données (⚠️ DANGEREUX) :
```bash
docker compose down -v
```

---

## Configuration de base

### Fichier de configuration principal

Le fichier `.env.prod` contient toutes les configurations importantes.

#### Localisation
```bash
/chemin/vers/check-du-matin-blade/.env.prod
```

#### Variables importantes

##### Configuration de l'application
```env
APP_NAME=Check du Matin
APP_ENV=production
APP_DEBUG=false
APP_URL=https://checking.c2s.fr
```

**Explications** :
- `APP_ENV=production` : Mode production (ne jamais mettre `local` en production)
- `APP_DEBUG=false` : Désactive l'affichage des erreurs détaillées (sécurité)
- `APP_URL` : L'URL complète de votre application

##### Configuration de la base de données
```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=check_du_matin
DB_USERNAME=laravel
DB_PASSWORD=laravel
```

**⚠️ Important** : Changez le mot de passe en production !

##### Configuration email
```env
MAIL_MAILER=smtp
MAIL_HOST=relais.services.c-2-s.info
MAIL_PORT=25
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
```

**Explications** :
- `MAIL_HOST` : L'adresse du serveur SMTP
- `MAIL_PORT` : Le port SMTP (généralement 25, 587 ou 465)
- `MAIL_USERNAME` et `MAIL_PASSWORD` : Si votre serveur SMTP nécessite une authentification

### Modifier la configuration

1. **Éditer le fichier** :
```bash
nano .env.prod
# ou
vi .env.prod
```

2. **Appliquer les changements** :
```bash
# Redémarrer l'application
docker compose restart app
```

**Note** : Certains changements nécessitent un redémarrage complet :
```bash
docker compose down
docker compose up -d
```

---

## Opérations courantes

### Voir les logs de l'application

#### Logs en temps réel
```bash
docker compose logs -f app
```

Appuyez sur `Ctrl+C` pour arrêter l'affichage.

#### Dernières lignes des logs
```bash
docker compose logs --tail=50 app
```

Affiche les 50 dernières lignes.

#### Logs de la base de données
```bash
docker compose logs db
```

### Vérifier l'état de l'application

#### État des conteneurs
```bash
docker compose ps
```

**Résultat attendu** :
```
NAME                        STATUS
check-du-matin-blade-app    Up
check-du-matin-blade-db     Up (healthy)
```

#### Vérifier l'espace disque
```bash
df -h
docker system df
```

#### Vérifier l'utilisation mémoire
```bash
docker stats
```

Appuyez sur `Ctrl+C` pour arrêter.

### Accéder au conteneur de l'application

Pour exécuter des commandes dans le conteneur :

```bash
docker compose exec app bash
```

Vous êtes maintenant dans le conteneur. Pour sortir, tapez `exit`.

### Exécuter des commandes Laravel

Sans entrer dans le conteneur :

```bash
# Voir toutes les commandes disponibles
docker compose exec app php artisan list

# Vider le cache
docker compose exec app php artisan cache:clear

# Voir les routes
docker compose exec app php artisan route:list
```

---

## Maintenance quotidienne

### Tâches automatiques

L'application exécute automatiquement certaines tâches :

1. **Création automatique de checks** : Toutes les 5 minutes
2. **Suppression des anciens checks** : Tous les jours à 2h du matin (checks de plus de 30 jours)

Ces tâches sont gérées automatiquement, vous n'avez rien à faire.

### Vérifications quotidiennes recommandées

#### 1. Vérifier que l'application fonctionne

```bash
# Vérifier l'état
docker compose ps

# Vérifier les logs récents
docker compose logs --tail=20 app
```

#### 2. Vérifier l'espace disque

```bash
df -h
```

Si l'espace disque est inférieur à 20%, nettoyez les anciens logs :
```bash
# Dans le conteneur
docker compose exec app bash
find storage/logs -name "*.log" -mtime +30 -delete
exit
```

#### 3. Vérifier les erreurs dans les logs

```bash
docker compose logs app | grep -i error
```

Si vous voyez des erreurs répétées, consultez la section "Résolution de problèmes".

### Nettoyage périodique

#### Nettoyer les logs anciens

```bash
# Supprimer les logs de plus de 30 jours
docker compose exec app find storage/logs -name "*.log" -mtime +30 -delete
```

#### Nettoyer le cache Docker

```bash
# Supprimer les images inutilisées
docker system prune -a

# ⚠️ Attention : Cela supprime toutes les images non utilisées
```

#### Nettoyer les anciens checks (manuellement)

Par défaut, les checks de plus de 30 jours sont supprimés automatiquement. Pour le faire manuellement :

```bash
docker compose exec app php artisan checks:delete-old --days=30
```

Pour supprimer les checks de plus de 60 jours :
```bash
docker compose exec app php artisan checks:delete-old --days=60
```

---

## Résolution de problèmes courants

### L'application ne démarre pas

#### Symptôme
```bash
docker compose ps
# Affiche "Exited" ou "Restarting"
```

#### Solutions

1. **Vérifier les logs** :
```bash
docker compose logs app
```

2. **Vérifier que le port 8001 n'est pas utilisé** :
```bash
netstat -tuln | grep 8001
```

Si le port est utilisé, changez-le dans `docker-compose.yml` :
```yaml
ports:
  - "8002:8000"  # Changez 8001 en 8002
```

3. **Vérifier les permissions** :
```bash
ls -la storage/
# Les dossiers doivent être accessibles en écriture
```

4. **Redémarrer complètement** :
```bash
docker compose down
docker compose up -d
```

### Erreur de connexion à la base de données

#### Symptôme
```
SQLSTATE[HY000] [2002] Connection refused
```

#### Solutions

1. **Vérifier que la base de données est démarrée** :
```bash
docker compose ps db
```

2. **Vérifier les logs de la base de données** :
```bash
docker compose logs db
```

3. **Redémarrer la base de données** :
```bash
docker compose restart db
# Attendre 30 secondes
docker compose restart app
```

4. **Vérifier la configuration dans `.env.prod`** :
```bash
grep DB_ .env.prod
```

### L'application est lente

#### Solutions

1. **Vider le cache** :
```bash
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan view:clear
```

2. **Recréer le cache** :
```bash
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

3. **Vérifier l'utilisation des ressources** :
```bash
docker stats
```

4. **Vérifier l'espace disque** :
```bash
df -h
```

### Les emails ne partent pas

#### Symptôme
```
Échec de l'envoi de l'email: Connection refused
```

#### Solutions

1. **Vérifier la configuration SMTP dans `.env.prod`** :
```bash
grep MAIL_ .env.prod
```

2. **Tester la connexion SMTP** (depuis le serveur) :
```bash
telnet relais.services.c-2-s.info 25
# ou
nc -zv relais.services.c-2-s.info 25
```

3. **Vérifier les logs** :
```bash
docker compose logs app | grep -i mail
```

4. **En développement local, utiliser le mode log** :
```env
MAIL_MAILER=log
```

### Erreur 500 (Erreur serveur)

#### Symptôme
L'application affiche "500 Internal Server Error"

#### Solutions

1. **Vérifier les logs détaillés** :
```bash
docker compose logs app | tail -100
```

2. **Activer temporairement le mode debug** (⚠️ UNIQUEMENT pour diagnostiquer) :
```env
APP_DEBUG=true
```
Puis redémarrer :
```bash
docker compose restart app
```

**⚠️ IMPORTANT** : Remettez `APP_DEBUG=false` après le diagnostic !

3. **Vérifier les permissions** :
```bash
docker compose exec app ls -la storage/
```

4. **Vérifier que la base de données est accessible** :
```bash
docker compose exec app php artisan migrate:status
```

### Les logos ne s'affichent pas

#### Symptôme
Les logos des clients ne s'affichent pas dans l'interface

#### Solutions

1. **Vérifier que le lien symbolique existe** :
```bash
docker compose exec app ls -la public/storage
```

Si le lien n'existe pas :
```bash
docker compose exec app php artisan storage:link
```

2. **Vérifier les permissions** :
```bash
docker compose exec app ls -la storage/app/public/logos/
```

3. **Vérifier que les fichiers existent** :
```bash
docker compose exec app ls -la storage/app/public/logos/
```

### La page est blanche

#### Solutions

1. **Vérifier les logs** :
```bash
docker compose logs app | tail -50
```

2. **Vérifier les erreurs PHP** :
```bash
docker compose exec app php -v
```

3. **Vider tous les caches** :
```bash
docker compose exec app php artisan optimize:clear
```

4. **Redémarrer l'application** :
```bash
docker compose restart app
```

---

## Sauvegarde et restauration

### Sauvegarde de la base de données

#### Sauvegarde manuelle

```bash
# Créer un dossier pour les sauvegardes
mkdir -p /chemin/vers/sauvegardes

# Faire la sauvegarde
docker compose exec db mysqldump -u laravel -plaravel check_du_matin > /chemin/vers/sauvegardes/backup_$(date +%Y%m%d_%H%M%S).sql
```

**Exemple** :
```bash
docker compose exec db mysqldump -u laravel -plaravel check_du_matin > ~/backups/backup_20250115_143000.sql
```

#### Sauvegarde automatique (script)

Créez un fichier `backup.sh` :

```bash
#!/bin/bash
BACKUP_DIR="/chemin/vers/sauvegardes"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/backup_$DATE.sql"

mkdir -p $BACKUP_DIR

docker compose exec -T db mysqldump -u laravel -plaravel check_du_matin > $BACKUP_FILE

# Compresser la sauvegarde
gzip $BACKUP_FILE

# Supprimer les sauvegardes de plus de 30 jours
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete

echo "Sauvegarde créée : $BACKUP_FILE.gz"
```

Rendre le script exécutable :
```bash
chmod +x backup.sh
```

L'exécuter :
```bash
./backup.sh
```

#### Planifier des sauvegardes automatiques

Ajoutez une tâche cron (éditez `crontab -e`) :

```bash
# Sauvegarde tous les jours à 2h du matin
0 2 * * * /chemin/vers/backup.sh
```

### Sauvegarde des fichiers

Les logos et fichiers sont stockés dans `storage/app/public/`.

#### Sauvegarde manuelle

```bash
# Créer une archive
tar -czf storage_backup_$(date +%Y%m%d).tar.gz storage/app/public/
```

#### Restauration

```bash
# Extraire l'archive
tar -xzf storage_backup_20250115.tar.gz
```

### Restauration de la base de données

#### ⚠️ ATTENTION : Cela remplace toutes les données actuelles !

```bash
# Restaurer depuis un fichier
docker compose exec -T db mysql -u laravel -plaravel check_du_matin < /chemin/vers/sauvegardes/backup_20250115_143000.sql
```

**Exemple** :
```bash
docker compose exec -T db mysql -u laravel -plaravel check_du_matin < ~/backups/backup_20250115_143000.sql
```

### Sauvegarde complète (base + fichiers)

Créez un script `full_backup.sh` :

```bash
#!/bin/bash
BACKUP_DIR="/chemin/vers/sauvegardes"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_BASE="$BACKUP_DIR/backup_$DATE"

mkdir -p $BACKUP_BASE

# Sauvegarde base de données
docker compose exec -T db mysqldump -u laravel -plaravel check_du_matin > $BACKUP_BASE/database.sql

# Sauvegarde fichiers
tar -czf $BACKUP_BASE/storage.tar.gz storage/app/public/

# Créer une archive complète
cd $BACKUP_DIR
tar -czf backup_$DATE.tar.gz backup_$DATE/
rm -rf backup_$DATE/

echo "Sauvegarde complète créée : $BACKUP_DIR/backup_$DATE.tar.gz"
```

---

## Mise à jour de l'application

### Préparation

1. **Faire une sauvegarde complète** (voir section précédente)

2. **Vérifier l'état actuel** :
```bash
git status
```

### Procédure de mise à jour

#### Étape 1 : Arrêter l'application

```bash
docker compose down
```

#### Étape 2 : Récupérer les nouvelles versions

```bash
# Si vous utilisez Git
git pull

# Sinon, téléchargez la nouvelle version
```

#### Étape 3 : Reconstruire l'image Docker

```bash
docker compose build --no-cache
```

Le `--no-cache` force la reconstruction complète (plus long mais plus sûr).

#### Étape 4 : Redémarrer l'application

```bash
docker compose up -d
```

#### Étape 5 : Appliquer les migrations de base de données

```bash
docker compose exec app php artisan migrate --force
```

#### Étape 6 : Vérifier que tout fonctionne

```bash
# Vérifier l'état
docker compose ps

# Vérifier les logs
docker compose logs app | tail -50
```

#### Étape 7 : Vider et recréer les caches

```bash
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

### En cas de problème lors de la mise à jour

1. **Arrêter l'application** :
```bash
docker compose down
```

2. **Restaurer la sauvegarde** (voir section Restauration)

3. **Redémarrer l'ancienne version** :
```bash
git checkout <ancienne-version>
docker compose build
docker compose up -d
```

4. **Contacter le support technique**

---

## Annexes

### Commandes Docker utiles

```bash
# Voir tous les conteneurs
docker ps -a

# Voir les images
docker images

# Voir l'utilisation des ressources
docker stats

# Nettoyer les conteneurs arrêtés
docker container prune

# Nettoyer les images inutilisées
docker image prune -a

# Voir les volumes
docker volume ls

# Supprimer un volume (⚠️ DANGEREUX)
docker volume rm <nom_volume>
```

### Commandes Laravel utiles

```bash
# Voir toutes les commandes
docker compose exec app php artisan list

# Voir les routes
docker compose exec app php artisan route:list

# Voir la configuration
docker compose exec app php artisan config:show

# Vider tous les caches
docker compose exec app php artisan optimize:clear

# Voir les migrations
docker compose exec app php artisan migrate:status
```

### Structure des fichiers importants

```
check-du-matin-blade/
├── .env.prod              # Configuration principale
├── docker-compose.yml     # Configuration Docker
├── Dockerfile            # Image Docker
├── storage/              # Fichiers de l'application
│   ├── logs/            # Logs de l'application
│   └── app/             # Fichiers uploadés (logos, etc.)
└── database/            # Migrations et seeders
```

### Contacts et support

En cas de problème que vous ne pouvez pas résoudre :

1. Consultez les logs : `docker compose logs app`
2. Vérifiez cette documentation
3. Contactez l'équipe technique avec :
   - La description du problème
   - Les logs d'erreur
   - Les étapes pour reproduire le problème

### Glossaire

- **Container/Conteneur** : Un environnement isolé qui exécute l'application
- **Docker** : Logiciel qui gère les conteneurs
- **Logs** : Fichiers qui enregistrent ce qui se passe dans l'application
- **Migration** : Modification de la structure de la base de données
- **Cache** : Stockage temporaire pour accélérer l'application
- **SMTP** : Protocole pour envoyer des emails
- **SSH** : Connexion sécurisée au serveur

---

**Version** : 1.0  
**Dernière mise à jour** : 2025-01-XX  
**Auteur** : Équipe Check du Matin

