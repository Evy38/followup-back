# FollowUp Backend 🎯

**Application de suivi de candidatures d'emploi** - API REST avec Symfony 7.3

## 📋 Vue d'ensemble

FollowUp Backend est une API REST complète permettant de gérer et suivre ses candidatures d'emploi. L'application offre un système complet de gestion des candidatures, entreprises, relances et réponses avec authentification JWT sécurisée.

### 🎯 Fonctionnalités principales

- ✅ **Gestion des candidatures** : Création, suivi et mise à jour
- ✅ **Suivi des relances** : Planification et historique des relances
- ✅ **Gestion des réponses** : Enregistrement des retours recruteurs
- ✅ **Base entreprises** : Référentiel des entreprises ciblées
- ✅ **Authentification JWT** : Sécurité par tokens
- ✅ **API documentée** : Documentation Swagger intégrée
- ✅ **Multi-utilisateurs** : Isolation des données par utilisateur

## 🛠️ Stack technique

| Technologie | Version | Usage |
|-------------|---------|-------|
| **PHP** | 8.2+ | Langage backend |
| **Symfony** | 7.3 | Framework web |
| **MySQL** | 8.0 | Base de données |
| **Doctrine** | - | ORM |
| **API Platform** | - | API REST + docs |
| **JWT** | - | Authentification |
| **Docker** | - | Conteneurisation |
| **PHPUnit** | - | Tests unitaires |

## 🚀 Installation

### Prérequis
- Docker & Docker Compose
- Git

### 🔧 Setup rapide

```bash
# Cloner le projet
git clone [URL_REPO]
cd followup-back

# Démarrer l'environnement Docker
docker-compose up -d

# Installation des dépendances
docker-compose exec app composer install

# Configuration JWT (générer les clés)
docker-compose exec app php bin/console lexik:jwt:generate-keypair

# Migrations & données de test
docker-compose exec app php bin/console doctrine:migrations:migrate -n
docker-compose exec app php bin/console doctrine:fixtures:load -n
```

### 🌐 Accès aux services

| Service | URL | Description |
|---------|-----|-------------|
| **API** | http://localhost:8080 | API REST |
| **Swagger** | http://localhost:8080/api/docs | Documentation API |
| **phpMyAdmin** | http://localhost:8081 | Interface DB |

**Base de données :**
- Host: `localhost:3306`
- Database: `followup`
- User: `followup`
- Password: `followup123`

## 📊 Modèle de données

### 🏗️ Entités principales

```
User
├── Candidature (1:N)
    ├── Entreprise (N:1)
    ├── Ville (N:1)
    ├── Canal (N:1)
    ├── Statut (N:1)
    ├── Reponse (1:N)
    ├── Relance (1:N)
    └── MotCle (N:N)
```

### 📝 Entités détaillées

- **User** : Utilisateurs avec authentification
- **Candidature** : Candidatures d'emploi (cœur métier)
- **Entreprise** : Base des entreprises ciblées
- **Ville** : Localisation des postes
- **Statut** : États des candidatures (En attente, Refusé, etc.)
- **Canal** : Sources de candidature (LinkedIn, Indeed, Site...)
- **Reponse** : Réponses reçues des recruteurs
- **Relance** : Relances effectuées
- **MotCle** : Tags pour catégoriser

## 🔐 Authentification

### JWT Configuration

```bash
# Générer les clés JWT
docker-compose exec app php bin/console lexik:jwt:generate-keypair
```

### 📡 Endpoints d'authentification

```http
POST /api/register
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "motdepasse"
}
```

```http
POST /api/login_check
Content-Type: application/json

{
  "username": "user@example.com", 
  "password": "motdepasse"
}
```

### 🔒 Utilisation du token

```http
GET /api/candidatures
Authorization: Bearer YOUR_JWT_TOKEN
```

## 📚 API Documentation

### 🌍 Accès Swagger
- **URL** : http://localhost:8080/api/docs
- **Format** : OpenAPI 3.0
- **Interactif** : Tests directs des endpoints

### 🎯 Endpoints principaux

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `GET` | `/api/candidatures` | Liste des candidatures |
| `POST` | `/api/candidatures` | Créer une candidature |
| `GET` | `/api/candidatures/{id}` | Détail candidature |
| `PUT` | `/api/candidatures/{id}` | Modifier candidature |
| `DELETE` | `/api/candidatures/{id}` | Supprimer candidature |
| `GET` | `/api/entreprises` | Liste des entreprises |
| `GET` | `/api/relances` | Liste des relances |

## 🧪 Tests

### Lancer les tests

```bash
# Tests unitaires complets
docker-compose exec app php bin/phpunit

# Tests avec couverture
docker-compose exec app php bin/phpunit --coverage-html coverage

# Tests spécifiques
docker-compose exec app php bin/phpunit tests/Service/UserServiceTest.php
```

### 📈 Couverture actuelle
- ✅ **UserService** : Création, modification, suppression
- ✅ **UserRepository** : Accès données et requêtes
- ✅ **Validation** : Contraintes métier et sécurité
- ✅ **Base de données** : Tests d'isolation

## 🗃️ Données de test

### Compte de test

```json
{
  "email": "test@example.com",
  "password": "test1234"
}
```

### Générer des données

```bash
# Charger les fixtures (données de démonstration)
docker-compose exec app php bin/console doctrine:fixtures:load -n
```

**Contenu généré :**
- 👤 5 utilisateurs de test
- 🏢 20 entreprises avec secteurs variés
- 📍 15 villes françaises
- 📋 50+ candidatures réalistes
- 📞 Relances et réponses associées

## 🔧 Configuration

### Variables d'environnement

```bash
# .env.local
DATABASE_URL="mysql://followup:followup123@db:3306/followup"
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase
```

### CORS (pour frontend)

```yaml
# config/packages/nelmio_cors.yaml
nelmio_cors:
    defaults:
        origin_regex: true
        allow_origin: ['^http://localhost:[0-9]+']
        allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']
        allow_headers: ['Content-Type', 'Authorization']
```

## 🐳 Docker Services

```yaml
services:
  app:      # PHP 8.2 + Apache + Symfony
  db:       # MySQL 8.0
  pma:      # phpMyAdmin
```

### Commandes Docker utiles

```bash
# Logs en temps réel
docker-compose logs -f

# Accéder au conteneur PHP
docker-compose exec app bash

# Redémarrer un service
docker-compose restart app

# Nettoyer et reconstruire
docker-compose down -v
docker-compose up --build
```

## 🚧 Développement

### Structure du projet

```
src/
├── Controller/       # Contrôleurs API
├── Entity/          # Entités Doctrine
├── Repository/      # Repositories personnalisés
├── Services/        # Logique métier
└── DataFixtures/    # Données de test

tests/
├── Service/         # Tests services
├── Repository/      # Tests repositories
└── DatabaseTestCase.php  # Classe de base tests DB
```

### Commandes utiles

```bash
# Créer une entité
docker-compose exec app php bin/console make:entity

# Créer une migration
docker-compose exec app php bin/console make:migration

# Appliquer les migrations
docker-compose exec app php bin/console doctrine:migrations:migrate

# Cache clear
docker-compose exec app php bin/console cache:clear
```

## 🎯 Roadmap

### ✅ Réalisé
- [x] API REST complète
- [x] Authentification JWT
- [x] Tests unitaires
- [x] Documentation Swagger
- [x] Docker setup
- [x] Fixtures réalistes

### 🚀 À venir
- [ ] Tests d'intégration API
- [ ] Filtrage avancé des candidatures
- [ ] Notifications email automatiques
- [ ] Export des données (CSV/PDF)
- [ ] Dashboard analytics
- [ ] Interface d'administration
- [ ] CI/CD Pipeline

## 🤝 Contribution

### Workflow
1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/nouvelle-fonctionnalite`)
3. Commit (`git commit -m 'Ajout nouvelle fonctionnalité'`)
4. Push (`git push origin feature/nouvelle-fonctionnalite`)
5. Créer une Pull Request

### Standards
- **PSR-12** : Style de code PHP
- **Tests** : Couverture obligatoire pour nouvelles features
- **Documentation** : Mise à jour du README si nécessaire

## 📄 Licence

Ce projet est sous licence **MIT** - voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 👥 Auteurs

- **Développeur Principal** - Cécile

---

> **Note :** Ce projet est conçu pour s'interfacer avec un frontend Angular. L'API est prête pour la production avec toutes les fonctionnalités de sécurité et de performance nécessaires.

**🔗 Liens utiles :**
- [Documentation Symfony](https://symfony.com/doc)
- [API Platform](https://api-platform.com/docs)
- [JWT Bundle](https://github.com/lexik/LexikJWTAuthenticationBundle)