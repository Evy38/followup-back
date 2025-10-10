# followup-back

Petit backend Symfony pour FollowUp — instructions d'installation et d'usage (focalisé Docker / dev local).

## Prérequis
- Docker Desktop installé (Windows / Mac / Linux)
- Docker Compose (fourni avec Docker Desktop)
- (Optionnel) client MySQL si tu veux te connecter depuis l'hôte

## Structure importante
- `docker-compose.yml` : définit les services `php`, `db` et `pma`.
- `docker/php/Dockerfile` : image PHP/Apache pour Symfony.
- `docker/db/init.sql` : script d'initialisation de la base de données (création des DB et de l'utilisateur).
- `.env.example` : exemple de variables d'environnement à copier en `.env`.

## Installer et démarrer (en local)
1. Copier l'exemple d'environnement et personnaliser si besoin :

```cmd
copy .env.example .env
```

2. Construire et lancer les services (en background) :

```cmd
docker compose up -d --build
```

3. Vérifier l'état des services :

```cmd
docker compose ps
docker compose logs -f db
```

4. Accéder à l'application
- Symfony (via le service `php`) : http://localhost:8080
- phpMyAdmin : http://localhost:8081 (utilisateur/mot de passe défini dans `.env` / `docker-compose.yml`)

## Fichier `.env` et variables importantes
- Le projet fournit `.env.example`. Copie‑le en `.env` et garde tes vrais secrets hors du dépôt.
- Variables utiles :
	- `MYSQL_ROOT_PASSWORD` : mot de passe root MySQL (dev)
	- `MYSQL_USER` / `MYSQL_PASSWORD` : utilisateur et mot de passe créés pour l'app
	- `DATABASE_URL` : connexion utilisée par Symfony

Exemple de `DATABASE_URL` dans `.env` pour ce setup :

```
DATABASE_URL=mysql://follow_user:root@db:3306/followup_db
```

## Dépannage rapide
- Erreur MySQL « data directory has files in it » :
	- Sur Windows, préfère un volume Docker nommé pour `/var/lib/mysql` (déjà configuré). Si tu as un dossier `./docker/db` avec des fichiers, supprime-le avant de recréer le conteneur ou utilise `docker volume rm` pour supprimer le volume Docker.
- Problèmes de permissions Symfony (cache / logs) :
	- Exécute `docker compose exec php bash` puis `chown -R www-data:www-data var/` ou installe les dépendances (`composer install`) à l’intérieur du conteneur.
- Vérifier les logs :
	- `docker compose logs -f php` ou `docker compose logs -f db`

## Commandes utiles
- Démarrer (build) : `docker compose up -d --build`
- Arrêter : `docker compose down`
- Recréer uniquement la DB (suppression du container + volume) :

```cmd
docker compose stop db
docker compose rm -f db
docker volume rm followup-back_db_data
docker compose up -d db
```

## Sécurité (notes importantes)
- Ne commite jamais de secrets (mot de passe, clé privée) dans le dépôt public.
- En production, n'expose pas le port MySQL sur l'hôte et utilise un gestionnaire de secrets.

## Aide — contact
Si tu veux que j'ajoute des scripts pour fixer automatiquement les permissions, ou que je replace les variables par défaut par des variables d'environnement dans `docker-compose.yml` (déjà fait), dis‑moi et je le fais.
