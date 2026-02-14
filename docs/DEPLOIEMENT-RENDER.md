# 🚀 Guide de Déploiement - FollowUp Backend sur Render

## 📋 Vue d'ensemble

Ce document explique le déploiement du backend Symfony FollowUp sur la plateforme Render, en utilisant Docker et PostgreSQL.

---

## 🏗️ Architecture de déploiement

### Infrastructure
- **Plateforme** : Render (PaaS - Platform as a Service)
- **Runtime** : Docker (container)
- **Base de données** : PostgreSQL 16 (managed par Render)
- **Région** : Frankfurt (EU) - conformité RGPD
- **Plan** : Free tier (gratuit)

### Composants déployés
1. **Service Web** : Container Docker avec Symfony + Apache
2. **Base de données** : PostgreSQL managée par Render
3. **Clés JWT** : Générées automatiquement au démarrage

---

## 📁 Fichiers de configuration

### 1. `Dockerfile.render`
Dockerfile multi-stage optimisé pour la production :
- **Stage 1 (Builder)** : Installation des dépendances Composer
- **Stage 2 (Runtime)** : Image finale légère avec uniquement le nécessaire

**Différences avec le Dockerfile de dev** :
- PostgreSQL au lieu de MySQL (`pdo_pgsql`)
- Pas de volumes (tout dans l'image)
- Port dynamique via variable `$PORT`
- Cache optimisé et non invalidé

### 2. `docker-entrypoint.sh`
Script exécuté au démarrage du container :
1. Génération des clés JWT (si absentes)
2. Attente de la disponibilité de PostgreSQL
3. Création de la base de données
4. Exécution des migrations Doctrine
5. Optimisation du cache Symfony
6. Configuration du port Apache
7. Démarrage d'Apache

### 3. `render.yaml`
Infrastructure as Code pour Render :
- Définit le service web et la base de données
- Configure toutes les variables d'environnement
- Spécifie la région, le plan, et les health checks

### 4. `.dockerignore`
Optimise le build Docker en excluant :
- Fichiers de dev (`.git`, `docker/`, etc.)
- Cache et logs
- Tests
- Documentation

---

## 🔧 Variables d'environnement

### Variables générées automatiquement
- `APP_SECRET` : Secret Symfony (généré par Render)
- `DATABASE_URL` : URL de connexion PostgreSQL

### Variables à configurer manuellement

#### Obligatoires
```env
JWT_PASSPHRASE=votre_passphrase_securisee
CORS_ALLOW_ORIGIN=https://votre-frontend.onrender.com
FRONTEND_URL=https://votre-frontend.onrender.com
FRONT_URL=https://votre-frontend.onrender.com
```

#### OAuth Google
```env
GOOGLE_CLIENT_ID=votre_client_id
GOOGLE_CLIENT_SECRET=votre_client_secret
GOOGLE_REDIRECT_URI=https://votre-backend.onrender.com/auth/google/callback
```

#### Mailer (Gmail)
```env
MAILER_DSN=gmail+smtp://votre-email@gmail.com:votre_app_password@default
```

⚠️ **Important** : Utiliser un mot de passe d'application Gmail, pas votre mot de passe principal

---

## 🚀 Procédure de déploiement

### Prérequis
- [x] Compte Render créé et lié à GitHub
- [x] Repository GitHub avec le code
- [x] Fichiers de configuration commitées

### Étapes

#### 1. Préparer le repository
```bash
# Créer le dossier pour le script d'entrypoint
mkdir -p docker/scripts

# Déplacer le script d'entrypoint
mv docker-entrypoint.sh docker/scripts/

# Ajouter les fichiers au repository
git add Dockerfile.render render.yaml .dockerignore docker/scripts/docker-entrypoint.sh
git commit -m "feat: Configuration déploiement Render"
git push origin main
```

#### 2. Créer le service sur Render
1. Se connecter à https://dashboard.render.com
2. Cliquer sur **"New +"** → **"Blueprint"**
3. Connecter le repository GitHub
4. Render détecte automatiquement le `render.yaml`
5. Cliquer sur **"Apply"**

#### 3. Configurer les variables d'environnement
Dans le dashboard Render → Service → Environment :
1. Ajouter `JWT_PASSPHRASE`
2. Ajouter les URLs frontend (CORS, FRONTEND_URL)
3. Ajouter les credentials Google OAuth
4. Ajouter le MAILER_DSN

#### 4. Vérifier le déploiement
1. Attendre la fin du build (5-10 minutes)
2. Accéder à l'URL fournie : `https://followup-backend.onrender.com`
3. Tester l'endpoint : `https://followup-backend.onrender.com/api`

---

## 🔍 Différences Dev vs Prod

| Aspect | Développement (Local) | Production (Render) |
|--------|----------------------|---------------------|
| **Base de données** | PostgreSQL 16 (Docker) | PostgreSQL 16 (Managed) |
| **Serveur Web** | Apache (Docker, port 8080) | Apache (Docker, port dynamique) |
| **Clés JWT** | Générées manuellement | Générées au démarrage |
| **Cache** | Fichiers (var/cache) | OPcache + Filesystem |
| **Mail** | Mailhog (SMTP local) | Gmail (SMTP réel) |
| **Redis** | Redis Docker | Non utilisé (optionnel) |
| **ENV** | `.env.local` | Variables Render |
| **Debug** | `APP_DEBUG=1` | `APP_DEBUG=0` |

---

## 📊 Monitoring et logs

### Accéder aux logs
Dashboard Render → Service → Logs

### Types de logs disponibles
- **Build logs** : Logs de construction de l'image Docker
- **Runtime logs** : Logs applicatifs (Symfony)
- **Events** : Événements de déploiement

### Health Check
Render vérifie automatiquement `/api` toutes les 30 secondes.

Si le health check échoue 3 fois de suite :
→ Redémarrage automatique du container

---

## 🔄 Mises à jour et rollback

### Déploiement automatique
Chaque push sur `main` déclenche un nouveau déploiement.

### Rollback manuel
Dashboard Render → Service → Settings → Deploy History
→ Sélectionner une version précédente → "Redeploy"

---

## 🐛 Troubleshooting

### Le container ne démarre pas
1. Vérifier les logs de build
2. Vérifier que PostgreSQL est bien créé
3. Vérifier les variables d'environnement obligatoires

### Erreur "Database connection failed"
1. Vérifier que `DATABASE_URL` est bien liée à la DB
2. Vérifier que la DB est dans la même région

### Erreur 500 sur /api
1. Vérifier les logs Symfony
2. Vérifier que les migrations ont été exécutées
3. Vérifier `APP_SECRET`

### JWT ne fonctionne pas
1. Vérifier `JWT_PASSPHRASE` dans les env vars
2. Vérifier les logs : les clés ont-elles été générées ?
3. Vérifier les permissions des fichiers JWT

---

## 📚 Ressources

- [Documentation Render](https://render.com/docs)
- [Déployer Symfony sur Render](https://render.com/docs/deploy-symfony)
- [PostgreSQL sur Render](https://render.com/docs/databases)

---

## 🎯 Conformité REAC CDA

Ce déploiement répond aux critères suivants :

✅ **Utiliser un gestionnaire de conteneurs** : Docker  
✅ **Paramétrer les livrables dans un serveur d'automatisation** : render.yaml  
✅ **Définir l'infrastructure utilisée** : PostgreSQL, Docker, Apache  
✅ **Créer un script d'intégration** : docker-entrypoint.sh  
✅ **Environnements de tests définis** : SIT/UAT/PROD (via render.yaml)  
✅ **Documentation technique** : Ce document  

---

**Auteur** : Cécile Morel  
**Date** : Février 2026  
**Version** : 1.0