# Document d'Exploitation (DEX) - Check du Matin

## 📋 Table des matières

1. [Introduction](#introduction)
2. [Environnement de production](#environnement-de-production)
3. [Procédures opérationnelles](#procédures-opérationnelles)
4. [Monitoring et supervision](#monitoring-et-supervision)
5. [Incidents et résolution](#incidents-et-résolution)
6. [Maintenance préventive](#maintenance-préventive)
7. [Sauvegarde et restauration](#sauvegarde-et-restauration)
8. [Procédures de mise à jour](#procédures-de-mise-à-jour)
9. [Contacts et escalade](#contacts-et-escalade)
10. [Annexes](#annexes)

---

## Introduction

### Objet du document

Ce Document d'Exploitation (DEX) décrit les procédures opérationnelles pour maintenir l'application **Check du Matin** en production. Il est destiné aux administrateurs système et aux équipes de support.

### Périmètre

- Procédures de démarrage/arrêt
- Monitoring et alertes
- Gestion des incidents
- Maintenance préventive
- Sauvegarde et restauration
- Mise à jour

### Responsabilités

- **Administrateur système** : Infrastructure, Docker, serveur
- **Support technique** : Application, base de données, logs
- **Développeur** : Corrections de bugs, évolutions

---

## Environnement de production

### Infrastructure

#### Serveur

- **OS** : Linux (Ubuntu/Debian recommandé)
- **Docker** : Version 20.10+
- **Docker Compose** : Version 2.0+
- **RAM minimale** : 2 GB
- **Espace disque** : 20 GB minimum
- **CPU** : 2 cores minimum

#### Services

| Service | Port | Description |
|---------|------|-------------|
| Application | 8001 | Interface web Laravel |
| MySQL | 3307 | Base de données |
| SMTP | 25 | Serveur email (externe) |

#### Répertoires

```
/chemin/vers/check-du-matin-blade/
├── .env.prod                    # Configuration production
├── docker-compose.yml           # Configuration Docker
├── storage/                     # Fichiers applicatifs
│   ├── logs/                   # Logs Laravel
│   └── app/public/logos/       # Logos clients
└── backups/                     # Sauvegardes (à créer)
```

### Configuration réseau

#### Firewall

Ports à ouvrir :
- **8001** : Application web (HTTP/HTTPS)
- **3307** : MySQL (uniquement localhost recommandé)

#### DNS

- **URL production** : `https://checking.c2s.fr`
- **Configuration** : Pointe vers le serveur sur le port 8001

### Variables d'environnement critiques

```env
# Production - NE JAMAIS MODIFIER SANS VALIDATION
APP_ENV=production
APP_DEBUG=false
APP_URL=https://checking.c2s.fr

# Base de données
DB_HOST=db
DB_DATABASE=check_du_matin
DB_USERNAME=laravel
DB_PASSWORD=<MOT_DE_PASSE_SECURISE>

# Email
MAIL_MAILER=smtp
MAIL_HOST=relais.services.c-2-s.info
MAIL_PORT=25
```

---

## Procédures opérationnelles

### Démarrage de l'application

#### Démarrage initial

```bash
# 1. Se connecter au serveur
ssh user@serveur

# 2. Aller dans le répertoire
cd /chemin/vers/check-du-matin-blade

# 3. Vérifier la configuration
cat .env.prod | grep -E "APP_ENV|APP_DEBUG|DB_"

# 4. Démarrer les services
docker compose up -d

# 5. Vérifier l'état
docker compose ps

# 6. Vérifier les logs
docker compose logs app | tail -50
```

**Temps de démarrage attendu** : 30-60 secondes

#### Vérifications post-démarrage

```bash
# 1. Vérifier que les conteneurs sont "Up"
docker compose ps
# Attendu : STATUS = "Up" ou "Up (healthy)"

# 2. Vérifier les logs d'erreur
docker compose logs app | grep -i error

# 3. Tester l'accès web
curl -I http://localhost:8001
# Attendu : HTTP/1.1 200 OK

# 4. Vérifier la connexion DB
docker compose exec app php artisan migrate:status
# Attendu : Liste des migrations sans erreur
```

### Arrêt de l'application

#### Arrêt normal

```bash
# Arrêt propre
docker compose down

# Vérification
docker compose ps
# Attendu : Aucun conteneur
```

#### Arrêt d'urgence

```bash
# Arrêt forcé
docker compose kill

# Nettoyage
docker compose down
```

**⚠️ Attention** : L'arrêt d'urgence peut causer une perte de données non sauvegardées.

### Redémarrage

#### Redémarrage simple

```bash
docker compose restart app
```

#### Redémarrage complet

```bash
docker compose down
docker compose up -d
```

**Quand utiliser** :
- Après modification de `.env.prod`
- Après modification de `docker-compose.yml`
- En cas de problème persistant

### Vérification de santé

#### Script de vérification

Créez `health-check.sh` :

```bash
#!/bin/bash

echo "=== Vérification de santé Check du Matin ==="
echo ""

# 1. Vérifier les conteneurs
echo "1. État des conteneurs :"
docker compose ps
echo ""

# 2. Vérifier l'espace disque
echo "2. Espace disque :"
df -h | grep -E "Filesystem|/dev/"
echo ""

# 3. Vérifier la mémoire
echo "3. Mémoire :"
free -h
echo ""

# 4. Vérifier les logs d'erreur récents
echo "4. Erreurs récentes (dernières 10 lignes) :"
docker compose logs app --tail=10 | grep -i error || echo "Aucune erreur"
echo ""

# 5. Tester l'accès web
echo "5. Test d'accès web :"
curl -s -o /dev/null -w "HTTP Status: %{http_code}\n" http://localhost:8001
echo ""

# 6. Vérifier la base de données
echo "6. Test de connexion DB :"
docker compose exec -T app php artisan migrate:status > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "✓ Base de données accessible"
else
    echo "✗ Erreur de connexion à la base de données"
fi
echo ""

echo "=== Fin de la vérification ==="
```

Rendre exécutable :
```bash
chmod +x health-check.sh
```

Exécuter :
```bash
./health-check.sh
```

---

## Monitoring et supervision

### Logs à surveiller

#### Logs applicatifs

```bash
# Logs en temps réel
docker compose logs -f app

# Logs d'erreur uniquement
docker compose logs app | grep -i error

# Logs des dernières 100 lignes
docker compose logs --tail=100 app
```

**Localisation** : `storage/logs/laravel.log` (dans le conteneur)

#### Logs système

```bash
# Logs Docker
journalctl -u docker

# Logs système
tail -f /var/log/syslog
```

### Métriques à surveiller

#### Espace disque

```bash
# Vérifier l'espace disque
df -h

# Vérifier l'utilisation par volume Docker
docker system df
```

**Seuil d'alerte** : < 20% d'espace libre

#### Mémoire

```bash
# Utilisation mémoire
free -h

# Mémoire par conteneur
docker stats --no-stream
```

**Seuil d'alerte** : > 80% d'utilisation

#### CPU

```bash
# Utilisation CPU
top
# ou
docker stats
```

**Seuil d'alerte** : > 80% d'utilisation constante

### Alertes recommandées

#### Configuration d'alertes (exemple avec cron)

Créez `monitor.sh` :

```bash
#!/bin/bash

ALERT_EMAIL="admin@example.com"
DISK_THRESHOLD=80
MEM_THRESHOLD=80

# Vérifier l'espace disque
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt $DISK_THRESHOLD ]; then
    echo "ALERTE: Espace disque à ${DISK_USAGE}%" | mail -s "Alerte Check du Matin" $ALERT_EMAIL
fi

# Vérifier les conteneurs
if ! docker compose ps | grep -q "Up"; then
    echo "ALERTE: Conteneurs arrêtés" | mail -s "Alerte Check du Matin" $ALERT_EMAIL
fi
```

Ajouter dans crontab (`crontab -e`) :
```bash
# Vérification toutes les heures
0 * * * * /chemin/vers/monitor.sh
```

### Dashboard de monitoring

#### Commandes utiles pour tableau de bord

```bash
# État des services
docker compose ps

# Utilisation des ressources
docker stats --no-stream

# Espace disque
df -h

# Dernières erreurs
docker compose logs app --tail=20 | grep -i error
```

---

## Incidents et résolution

### Procédure de gestion d'incident

#### 1. Détection

- **Utilisateur** : Signale un problème
- **Monitoring** : Alerte automatique
- **Logs** : Détection d'erreurs

#### 2. Diagnostic

```bash
# 1. Vérifier l'état des conteneurs
docker compose ps

# 2. Consulter les logs récents
docker compose logs app --tail=100

# 3. Vérifier les ressources
docker stats --no-stream
df -h
free -h

# 4. Tester l'accès
curl -I http://localhost:8001
```

#### 3. Classification

| Niveau | Description | Action |
|--------|-------------|--------|
| **Critique** | Application inaccessible | Intervention immédiate |
| **Majeur** | Fonctionnalité majeure indisponible | Intervention sous 1h |
| **Mineur** | Fonctionnalité secondaire indisponible | Intervention sous 24h |

#### 4. Résolution

Voir section "Résolution de problèmes courants" ci-dessous.

#### 5. Communication

- Informer les utilisateurs si nécessaire
- Documenter l'incident
- Mettre à jour les procédures si besoin

### Résolution de problèmes courants

#### Application inaccessible (500)

**Symptômes** :
- Page blanche
- Erreur 500
- Timeout

**Actions** :

```bash
# 1. Vérifier les logs
docker compose logs app | tail -100

# 2. Vérifier les conteneurs
docker compose ps

# 3. Redémarrer l'application
docker compose restart app

# 4. Si problème persiste, redémarrer complètement
docker compose down
docker compose up -d

# 5. Vérifier les permissions
docker compose exec app ls -la storage/
```

#### Base de données inaccessible

**Symptômes** :
- Erreur "Connection refused"
- Erreur SQL

**Actions** :

```bash
# 1. Vérifier l'état du conteneur DB
docker compose ps db

# 2. Vérifier les logs DB
docker compose logs db | tail -50

# 3. Redémarrer la base de données
docker compose restart db

# 4. Attendre 30 secondes puis redémarrer l'app
sleep 30
docker compose restart app

# 5. Vérifier la connexion
docker compose exec app php artisan migrate:status
```

#### Emails ne partent pas

**Symptômes** :
- Erreur "Connection refused" dans les logs
- Emails non reçus

**Actions** :

```bash
# 1. Vérifier la configuration SMTP
docker compose exec app env | grep MAIL_

# 2. Tester la connexion SMTP (depuis le serveur)
telnet relais.services.c-2-s.info 25

# 3. Vérifier les logs
docker compose logs app | grep -i mail

# 4. Vérifier le firewall
iptables -L | grep 25
```

#### Espace disque insuffisant

**Symptômes** :
- Erreur "No space left on device"
- Application lente

**Actions** :

```bash
# 1. Identifier les gros fichiers
du -sh /var/lib/docker/*
du -sh storage/*

# 2. Nettoyer les logs anciens
docker compose exec app find storage/logs -name "*.log" -mtime +30 -delete

# 3. Nettoyer Docker
docker system prune -a

# 4. Supprimer les anciennes images
docker image prune -a

# 5. Si nécessaire, supprimer les anciens checks
docker compose exec app php artisan checks:delete-old --days=60
```

#### Application lente

**Symptômes** :
- Temps de chargement > 5 secondes
- Timeouts

**Actions** :

```bash
# 1. Vérifier les ressources
docker stats
df -h
free -h

# 2. Vider le cache
docker compose exec app php artisan optimize:clear

# 3. Recréer le cache
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache

# 4. Vérifier les requêtes lentes (si possible)
docker compose exec db mysql -u laravel -plaravel -e "SHOW PROCESSLIST;"
```

### Escalade

Si le problème ne peut pas être résolu avec les procédures ci-dessus :

1. **Documenter** : Logs, erreurs, actions tentées
2. **Contacter** : Équipe de développement
3. **Fournir** : 
   - Description du problème
   - Logs complets
   - Actions déjà tentées
   - Impact utilisateurs

---

## Maintenance préventive

### Tâches quotidiennes

#### Vérification matinale

```bash
# 1. État des conteneurs
docker compose ps

# 2. Logs d'erreur de la nuit
docker compose logs app --since 12h | grep -i error

# 3. Espace disque
df -h

# 4. Vérifier les sauvegardes
ls -lh /chemin/vers/backups/ | tail -5
```

### Tâches hebdomadaires

#### Nettoyage des logs

```bash
# Supprimer les logs de plus de 30 jours
docker compose exec app find storage/logs -name "*.log" -mtime +30 -delete
```

#### Vérification de l'intégrité

```bash
# Vérifier la base de données
docker compose exec db mysqlcheck -u laravel -plaravel check_du_matin

# Vérifier les permissions
docker compose exec app ls -la storage/
```

### Tâches mensuelles

#### Audit de sécurité

```bash
# 1. Vérifier les utilisateurs actifs
docker compose exec app php artisan tinker
>>> User::where('updated_at', '>', now()->subDays(30))->count();

# 2. Vérifier les logs de sécurité
docker compose logs app | grep -i "unauthorized\|forbidden"

# 3. Vérifier les permissions fichiers
find storage/ -type f -perm -o+w
```

#### Optimisation

```bash
# 1. Optimiser la base de données
docker compose exec db mysqlcheck -u laravel -plaravel --optimize check_du_matin

# 2. Nettoyer Docker
docker system prune -a

# 3. Vérifier les index
docker compose exec db mysql -u laravel -plaravel check_du_matin -e "SHOW INDEX FROM checks;"
```

### Tâches planifiées automatiques

L'application exécute automatiquement :

1. **Création de checks** : Toutes les 5 minutes
2. **Suppression des anciens checks** : Tous les jours à 2h

Vérifier que le scheduler fonctionne :

```bash
docker compose logs app | grep -i "schedule"
```

---

## Sauvegarde et restauration

### Stratégie de sauvegarde

#### Fréquence

- **Base de données** : Quotidienne à 2h du matin
- **Fichiers** : Hebdomadaire
- **Configuration** : À chaque modification

#### Rétention

- **Quotidiennes** : 7 jours
- **Hebdomadaires** : 4 semaines
- **Mensuelles** : 12 mois

### Procédure de sauvegarde

#### Sauvegarde automatique

Créez `backup-automatic.sh` :

```bash
#!/bin/bash

BACKUP_DIR="/chemin/vers/backups"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_BASE="$BACKUP_DIR/backup_$DATE"

mkdir -p $BACKUP_BASE

# Sauvegarde base de données
echo "Sauvegarde base de données..."
docker compose exec -T db mysqldump -u laravel -plaravel check_du_matin > $BACKUP_BASE/database.sql

# Compression
gzip $BACKUP_BASE/database.sql

# Sauvegarde fichiers
echo "Sauvegarde fichiers..."
tar -czf $BACKUP_BASE/storage.tar.gz storage/app/public/

# Sauvegarde configuration
echo "Sauvegarde configuration..."
cp .env.prod $BACKUP_BASE/.env.prod

# Créer archive complète
cd $BACKUP_DIR
tar -czf backup_$DATE.tar.gz backup_$DATE/
rm -rf backup_$DATE/

# Supprimer les sauvegardes de plus de 30 jours
find $BACKUP_DIR -name "backup_*.tar.gz" -mtime +30 -delete

echo "Sauvegarde complète créée : $BACKUP_DIR/backup_$DATE.tar.gz"
```

Planifier dans crontab :
```bash
# Tous les jours à 2h du matin
0 2 * * * /chemin/vers/backup-automatic.sh >> /var/log/backup.log 2>&1
```

#### Sauvegarde manuelle

```bash
# Base de données uniquement
docker compose exec -T db mysqldump -u laravel -plaravel check_du_matin > backup_$(date +%Y%m%d).sql

# Fichiers uniquement
tar -czf storage_backup_$(date +%Y%m%d).tar.gz storage/app/public/
```

### Procédure de restauration

#### ⚠️ ATTENTION : La restauration remplace toutes les données actuelles !

#### Restauration complète

```bash
# 1. Arrêter l'application
docker compose down

# 2. Extraire la sauvegarde
cd /chemin/vers/backups
tar -xzf backup_20250115_020000.tar.gz

# 3. Restaurer la base de données
gunzip backup_20250115_020000/database.sql.gz
docker compose up -d db
sleep 30
docker compose exec -T db mysql -u laravel -plaravel check_du_matin < backup_20250115_020000/database.sql

# 4. Restaurer les fichiers
tar -xzf backup_20250115_020000/storage.tar.gz

# 5. Restaurer la configuration (si nécessaire)
cp backup_20250115_020000/.env.prod .env.prod

# 6. Redémarrer l'application
docker compose up -d

# 7. Vérifier
docker compose logs app | tail -50
```

#### Restauration partielle (base de données uniquement)

```bash
# 1. Arrêter l'application
docker compose down

# 2. Restaurer la base de données
docker compose up -d db
sleep 30
docker compose exec -T db mysql -u laravel -plaravel check_du_matin < backup_20250115.sql

# 3. Redémarrer l'application
docker compose up -d
```

### Test de restauration

**Recommandation** : Tester la restauration tous les mois sur un environnement de test.

---

## Procédures de mise à jour

### Préparation

#### 1. Vérifier la version actuelle

```bash
# Si Git est utilisé
git log -1

# Vérifier les migrations
docker compose exec app php artisan migrate:status
```

#### 2. Faire une sauvegarde complète

```bash
./backup-automatic.sh
```

#### 3. Lire les notes de version

Consulter le CHANGELOG ou les notes de release.

### Procédure de mise à jour

#### Mise à jour standard

```bash
# 1. Arrêter l'application
docker compose down

# 2. Récupérer les nouvelles versions
git pull
# ou télécharger la nouvelle version

# 3. Vérifier les changements de configuration
diff .env.prod .env.example

# 4. Reconstruire l'image
docker compose build --no-cache

# 5. Démarrer l'application
docker compose up -d

# 6. Appliquer les migrations
docker compose exec app php artisan migrate --force

# 7. Vider et recréer les caches
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache

# 8. Vérifier
docker compose logs app | tail -50
docker compose ps
```

#### Mise à jour avec rollback

```bash
# 1. Sauvegarder la version actuelle
git tag backup-$(date +%Y%m%d)
git push origin backup-$(date +%Y%m%d)

# 2. Suivre la procédure de mise à jour standard

# 3. En cas de problème, rollback
git checkout backup-20250115
docker compose build
docker compose up -d
# Restaurer la base de données si nécessaire
```

### Vérification post-mise à jour

```bash
# 1. Vérifier l'état
docker compose ps

# 2. Vérifier les logs
docker compose logs app | tail -100

# 3. Tester l'accès web
curl -I http://localhost:8001

# 4. Tester une fonctionnalité clé (ex: connexion)
# Via l'interface web

# 5. Vérifier les migrations
docker compose exec app php artisan migrate:status
```

---

## Contacts et escalade

### Contacts techniques

| Rôle | Contact | Disponibilité |
|------|---------|---------------|
| Administrateur système | admin@example.com | 24/7 |
| Support technique | support@example.com | 9h-18h |
| Développeur | dev@example.com | Sur appel |

### Procédure d'escalade

1. **Niveau 1** : Support technique (problèmes courants)
2. **Niveau 2** : Administrateur système (problèmes infrastructure)
3. **Niveau 3** : Développeur (bugs applicatifs)

### Informations à fournir lors d'un appel

- Description du problème
- Heure de survenue
- Impact utilisateurs
- Actions déjà tentées
- Logs d'erreur
- Captures d'écran (si applicable)

---

## Annexes

### Commandes de référence

#### Docker

```bash
# État
docker compose ps
docker stats

# Logs
docker compose logs -f app
docker compose logs --tail=100 app

# Contrôle
docker compose up -d
docker compose down
docker compose restart app

# Maintenance
docker system prune -a
docker volume ls
```

#### Laravel

```bash
# Cache
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:cache

# Base de données
docker compose exec app php artisan migrate:status
docker compose exec app php artisan migrate --force

# Maintenance
docker compose exec app php artisan checks:delete-old
```

#### Système

```bash
# Ressources
df -h
free -h
top

# Réseau
netstat -tuln | grep 8001
curl -I http://localhost:8001
```

### Checklist de démarrage

- [ ] Docker et Docker Compose installés
- [ ] Fichier `.env.prod` configuré
- [ ] Ports 8001 et 3307 disponibles
- [ ] Espace disque suffisant (> 20 GB)
- [ ] Accès SMTP configuré
- [ ] Sauvegardes planifiées

### Checklist de maintenance quotidienne

- [ ] Vérifier l'état des conteneurs
- [ ] Consulter les logs d'erreur
- [ ] Vérifier l'espace disque
- [ ] Vérifier les sauvegardes de la nuit

### Checklist de maintenance hebdomadaire

- [ ] Nettoyer les logs anciens
- [ ] Vérifier l'intégrité de la base de données
- [ ] Vérifier les permissions fichiers
- [ ] Réviser les logs de sécurité

---

**Version** : 1.0  
**Dernière mise à jour** : 2025-01-XX  
**Auteur** : Équipe Check du Matin

