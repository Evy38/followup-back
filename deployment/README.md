# Déploiement – FollowUp Backend (Symfony)

## Architecture de déploiement

Le projet dispose de deux modes de déploiement, adaptés selon le contexte :

### 1. Déploiement via conteneur Docker (cible : Render)

`Dockerfile.render` à la racine du projet est l'image de production.
Il utilise un **multi-stage build** : une image builder installe les dépendances,
une image finale allégée accueille uniquement le runtime.

Au démarrage du conteneur, `docker/scripts/docker-entrypoint.sh` :
- attend que la base de données soit disponible
- applique les migrations Doctrine
- génère les clés JWT si absentes
- démarre Apache

Ce Dockerfile est utilisé directement sur **Render** via son intégration Docker native
(Render détecte automatiquement un `Dockerfile` ou un fichier nommé explicitement).

### 2. Déploiement manuel sur serveur (script)

`deployment/deploy.sh` automatise les étapes de déploiement sur un serveur classique :

```bash
chmod +x deployment/deploy.sh
./deployment/deploy.sh sit   # environnement d'intégration
./deployment/deploy.sh uat   # environnement de validation
./deployment/deploy.sh prod  # production (confirmation manuelle requise)
```

Étapes du script :
1. Vérification de l'environnement PHP/Composer
2. Installation des dépendances sans dépendances de développement
3. Nettoyage et rechargement du cache Symfony
4. Backup de la base de données (simulé — commande `pg_dump` à adapter)
5. Déploiement du code (simulé — commande `rsync` + `ssh` à adapter)
6. Vérification post-déploiement via endpoint `/health`

> Les étapes de déploiement sont simulées dans le cadre CDA.
> En production réelle, elles utiliseraient les accès SSH et credentials du serveur cible.

### 3. Pipeline CI/CD GitHub Actions

Voir `.github/workflows/` :
- `ci-backend.yml` : exécuté automatiquement sur chaque push/PR vers `main`
- `cd-backend.yml` : déclenché manuellement, cible SIT / UAT / PROD

## Environnements

| Environnement | Rôle |
|---|---|
| SIT | System Integration Testing — tests internes |
| UAT | User Acceptance Testing — validation utilisateur |
| PROD | Production — avec backup préalable obligatoire |

Les secrets (APP_SECRET, DATABASE_URL, JWT_PASSPHRASE…) sont configurés
dans les **GitHub Environments** pour isoler les accès par environnement.
