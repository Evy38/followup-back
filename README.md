# FollowUp – Backend API 🎯

**API REST sécurisée de suivi de candidatures d'emploi**  
Projet de fin de formation – **Titre Professionnel Concepteur Développeur d'Applications (CDA)**

[![Tests](https://img.shields.io/badge/tests-49%20passing-brightgreen)](tests/)
[![Coverage](https://img.shields.io/badge/coverage-75%25-brightgreen)](tests/)
[![PHP](https://img.shields.io/badge/PHP-8.2-blue)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-7.3-black)](https://symfony.com/)

---

## 📌 Présentation du projet

FollowUp est une plateforme web permettant aux chercheurs d'emploi de **centraliser et automatiser le suivi de leurs candidatures** :

- 📊 Tableau de bord des candidatures (attente, échanges, entretien, résultat)
- 🏢 Gestion des entreprises contactées
- 🔔 Relances automatiques programmées (J+7, J+14, J+21)
- 📅 Suivi des entretiens (prévus et passés)
- 🔍 Recherche d'offres via l'API Adzuna
- 📈 Statistiques et indicateurs de performance

Le backend expose une **API REST sécurisée** conforme aux standards **OpenAPI 3.1**, conçue pour être consommée par un frontend Angular.

---

## 🎯 Objectifs pédagogiques (REAC CDA)

Ce projet démontre les compétences du **Titre Professionnel CDA** (TP-01281 v04) :

### **Activité Type 1 : Développer la partie back-end d'une application web**
- Concevoir et développer une **API REST sécurisée** (Symfony 7.3 + API Platform 4.2)
- Implémenter une **architecture en couches** (Controller/Service/Repository/Entity)
- Garantir la **persistance des données** (Doctrine ORM + MySQL 8.0)
- Respecter les **bonnes pratiques** (SOLID, DRY, Clean Code)

### **Activité Type 3 : Préparer le déploiement d'une application sécurisée**
- Élaborer et exécuter un **plan de tests** (56 tests automatisés)
- Sécuriser l'application contre les **vulnérabilités OWASP Top 10**
- Documenter l'architecture technique et les choix de conception
- Conteneuriser l'application avec **Docker**

---

## 🏗️ Architecture Backend

### **Séparation des responsabilités (Clean Architecture)**

```
┌─────────────┐
│ Controller  │  ← Réception requêtes HTTP, validation, réponses JSON
└──────┬──────┘
       │
┌──────▼──────┐
│   Service   │  ← Logique métier, règles de gestion, sécurité
└──────┬──────┘
       │
┌──────▼──────┐
│ Repository  │  ← Accès données (Doctrine ORM)
└──────┬──────┘
       │
┌──────▼──────┐
│   Entity    │  ← Modèle de données
└─────────────┘
```

### **Composants principaux**

**Controllers (11 fichiers)**
- Reçoivent et valident les requêtes HTTP
- Gèrent les codes de réponse HTTP (200, 201, 400, 401, 403, 404)
- Délèguent la logique métier aux services
- Exemples : `RegisterController`, `ForgotPasswordController`, `CandidatureController`

**Services (8 fichiers)**
- Contiennent la logique métier complexe
- Centralisent la sécurité (hashage, validation, tokens)
- Facilitent les tests unitaires
- Exemples : `UserService`, `RelanceService`, `EmailVerificationService`, `AdzunaService`

**Repositories (6 fichiers)**
- Gèrent exclusivement l'accès aux données
- Requêtes personnalisées Doctrine DQL
- Exemples : `UserRepository`, `CandidatureRepository`, `EntretienRepository`

**Entities (6 fichiers)**
- Modèle de données avec annotations Doctrine
- Utilisation des **PHP 8.1 Enums** (StatutEntretien, ResultatEntretien, StatutReponse)
- Relations bidirectionnelles (OneToMany, ManyToOne)

👉 Cette architecture respecte les principes **SOLID** et les standards **Symfony Best Practices**.

---

## 🔐 Sécurité & Authentification

### **Authentification JWT (JSON Web Token)**

```
Frontend → POST /api/login_check → JWT Token → Stockage sécurisé
                                  ↓
                        Requêtes authentifiées avec
                        Authorization: Bearer {token}
```

- **Bundle** : LexikJWTAuthenticationBundle
- **Algorithme** : RS256 (clés asymétriques)
- **Durée de vie** : 1 heure (configurable)
- **API stateless** : Pas de session serveur

### **OAuth 2.0 avec Google**

- Connexion via **Google OAuth 2.0**
- Création automatique de l'utilisateur si inexistant
- Vérification automatique de l'email (prouvé par OAuth)
- Génération d'un JWT après authentification OAuth
- **Cookie HTTP-only sécurisé** (protection XSS/CSRF)

### **Sécurité des mots de passe**

- Hash avec **bcrypt** via `UserPasswordHasherInterface`
- Algorithme : `bcrypt` avec coût 13
- Jamais stockés en clair
- **Politique de complexité** :
  - Minimum 8 caractères
  - Au moins 1 majuscule
  - Au moins 1 chiffre
  - Regex : `/^(?=.*[A-Z])(?=.*\d).{8,}$/`

### **Protection OWASP Top 10 (2021)**

| Vulnérabilité | Protection implémentée |
|---------------|------------------------|
| **A01 - Broken Access Control** | Vérification propriétaire + RBAC (ROLE_USER, ROLE_ADMIN) |
| **A02 - Cryptographic Failures** | bcrypt pour mots de passe, JWT RS256, tokens sécurisés |
| **A03 - Injection** | Requêtes paramétrées Doctrine, validation inputs |
| **A04 - Insecure Design** | Séparation responsabilités, architecture en couches |
| **A05 - Security Misconfiguration** | Environnements séparés (dev/prod), secrets externalisés |
| **A06 - Vulnerable Components** | Dépendances à jour (Symfony 7.3, PHP 8.2) |
| **A07 - Authentication Failures** | JWT + OAuth, limitation tentatives, tokens expirables |
| **A08 - Data Integrity Failures** | Validation Symfony Validator, contraintes Doctrine |
| **A09 - Logging Failures** | Logs centralisés (Monolog), alertes erreurs critiques |
| **A10 - SSRF** | Validation URLs externes, timeout API Adzuna |

---

## 🔄 Fonctionnalités avancées

### **Réinitialisation de mot de passe sécurisée**

Workflow en 2 étapes :

1. **Demande** : `POST /api/password/request`
   - Génération token cryptographique (64 caractères)
   - Expiration : 1 heure
   - Email avec lien de réinitialisation
   - **Anti-énumération** : Même réponse que l'email existe ou non

2. **Réinitialisation** : `POST /api/password/reset`
   - Validation du token et de l'expiration
   - Vérification complexité nouveau mot de passe
   - Suppression token après usage (usage unique)

### **Vérification d'email**

- Token de vérification envoyé à l'inscription
- Expiration : 24 heures
- Endpoint : `GET /api/verify-email?token={token}`
- Renvoi possible : `POST /api/verify-email/resend`

### **Relances automatiques**

- Planification automatique à J+7, J+14, J+21
- Génération lors de la création de candidature
- Service dédié : `RelanceService`
- Workflow : `CandidatureRelancesSubscriber` (Event Doctrine)

### **Synchronisation statuts**

- **Listener Doctrine** : `CandidatureStatutSyncService`
- Synchronise automatiquement :
  - `candidature.statut_reponse` ↔ `entretien.resultat`
  - Mise à jour bidirectionnelle temps réel

### **Intégration API externe**

- **Service Adzuna** : Recherche d'offres d'emploi
- Transformation JSON → DTO applicatif
- Mapping types de contrats (anglais → français)
- Gestion erreurs et fallback

---

## 🧪 Tests & Qualité du code

### **Stratégie de tests (Plan de tests conforme REAC)**

**56 tests automatisés** répartis en :

| Type | Quantité | Exemples |
|------|----------|----------|
| **Tests unitaires** | 23 | Services isolés (UserService, RelanceService, EmailVerificationService) |
| **Tests d'intégration** | 30 | Endpoints API complets (auth, candidatures, profil) |
| **Tests de sécurité** | 8 | JWT, RBAC, validation inputs, OWASP |
| **Tests de non-régression** | 3 | Workflows critiques (register, reset password, verify email) |

**Couverture de code** : ~75% (cible 70% minimum)

**Temps d'exécution** : < 15 secondes pour 56 tests

### **Tests unitaires (exemples)**

```php
// Test du service UserService
public function testCreateUserWithDuplicateEmailThrowsException(): void
{
    $this->expectException(ConflictHttpException::class);
    
    $user1 = new User();
    $user1->setEmail('duplicate@example.com');
    $this->userService->create($user1);
    
    $user2 = new User();
    $user2->setEmail('duplicate@example.com');
    $this->userService->create($user2); // Doit lever une exception
}
```

### **Tests d'intégration (exemples)**

```php
// Test d'un endpoint protégé
public function testProfileRequiresAuthentication(): void
{
    $client = static::createClient();
    $client->request('GET', '/api/user/profile');
    
    $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
}
```

### **Commandes de test**

```bash
# Tous les tests
docker compose exec php ./vendor/bin/phpunit

# Avec couverture de code
docker compose exec php ./vendor/bin/phpunit --coverage-html coverage/

# Tests spécifiques
docker compose exec php ./vendor/bin/phpunit tests/Service/
docker compose exec php ./vendor/bin/phpunit tests/Api/
```

### **Documentation des tests**

📄 **Plan de tests complet** : [docs/PLAN_DE_TESTS_COURT.md](docs/PLAN_DE_TESTS_COURT.md)

---

## 📦 Stack technique

| Composant | Technologie | Version |
|-----------|-------------|---------|
| **Langage** | PHP | 8.2 |
| **Framework** | Symfony | 7.3 |
| **API** | API Platform | 4.2 |
| **Base de données** | MySQL | 8.0 |
| **ORM** | Doctrine | 3.5 |
| **Authentification** | JWT + OAuth Google | - |
| **Tests** | PHPUnit | 11.5 |
| **Conteneurisation** | Docker + Docker Compose | - |
| **Validation** | Symfony Validator | 7.3 |
| **Email** | Symfony Mailer | 7.3 |

**Bundles principaux :**
- `lexik/jwt-authentication-bundle` : Authentification JWT
- `api-platform/core` : API REST auto-documentée
- `doctrine/doctrine-bundle` : ORM et persistence
- `symfony/security-bundle` : Sécurité et autorisations
- `knpuniversity/oauth2-client-bundle` : OAuth Google

---

## 📂 Modèle de données

```
┌─────────┐
│  User   │────┐
└─────────┘    │
               │ 1:N
        ┌──────▼──────────┐
        │  Candidature    │
        └────┬───┬───┬────┘
             │   │   │
        ┌────┘   │   └────┐
        │        │        │
   ┌────▼────┐ ┌▼─────┐ ┌▼────────┐
   │Entreprise│ │Statut│ │ Relance │
   └─────────┘ └──────┘ └─────────┘
        │
   ┌────▼────────┐
   │  Entretien  │
   └─────────────┘
```

### **Entités principales**

**User**
- Email unique (authentification)
- Mot de passe hashé (bcrypt)
- Rôles (ROLE_USER, ROLE_ADMIN)
- Google ID (OAuth optionnel)
- Tokens de vérification/reset

**Candidature**
- Entreprise (relation ManyToOne)
- Date de candidature
- Titre du poste
- Statut de réponse (Enum PHP 8.1)
- Lien vers l'annonce
- ID externe (Adzuna)

**Entretien**
- Date et heure
- Statut (prévu/passé)
- Résultat (engagé/négatif/attente)
- Relation avec Candidature

**Relance**
- Date planifiée
- Type (email, téléphone)
- Rang (1, 2, 3)
- Statut (faite/non faite)

**Entreprise**
- Nom
- Relations avec Candidatures

**Statut**
- Libellé (attente, échanges, entretien, etc.)
- Synchronisé avec `StatutReponse` (Enum)

---

## 🚀 Installation (environnement local)

### **Prérequis**

- Docker Desktop 4.0+ et Docker Compose
- Git

### **1. Clone du projet**

```bash
git clone https://github.com/votre-username/followup-back.git
cd followup-back
```

### **2. Configuration de l'environnement**

```bash
# Copie du fichier d'environnement
cp .env .env.local

# Modifie les variables suivantes dans .env.local :
# - DATABASE_URL
# - JWT_PASSPHRASE
# - GOOGLE_CLIENT_ID
# - GOOGLE_CLIENT_SECRET
# - ADZUNA_APP_ID
# - ADZUNA_APP_KEY
```

### **3. Démarrage des containers**

```bash
docker compose up -d
```

### **4. Installation des dépendances**

```bash
docker compose exec php composer install
```

### **5. Génération des clés JWT**

```bash
docker compose exec php php bin/console lexik:jwt:generate-keypair
```

### **6. Création de la base de données**

```bash
# Création de la base
docker compose exec php php bin/console doctrine:database:create

# Exécution des migrations
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

# (Optionnel) Chargement des fixtures de démonstration
docker compose exec php php bin/console doctrine:fixtures:load --no-interaction
```

### **7. Lancement des tests**

```bash
# Création de la base de test
docker compose exec php php bin/console doctrine:database:create --env=test

# Exécution des migrations (test)
docker compose exec php php bin/console doctrine:migrations:migrate --env=test --no-interaction

# Lancement des tests
docker compose exec php ./vendor/bin/phpunit
```

### **8. Accès à l'API**

- **API** : http://localhost:8000
- **Documentation Swagger** : http://localhost:8000/api/docs
- **Base de données** : `localhost:3306` (phpMyAdmin disponible sur le port 8080)

---

## 📖 Documentation API

### **Swagger / OpenAPI**

Documentation interactive disponible : http://localhost:8000/api/docs

### **Endpoints principaux**

**Authentification**
```
POST   /api/register           Inscription
POST   /api/login_check        Connexion (JWT)
GET    /api/google/auth        Initiation OAuth Google
GET    /api/google/callback    Callback OAuth
POST   /api/password/request   Demande reset password
POST   /api/password/reset     Reset password
GET    /api/verify-email       Vérification email
POST   /api/verify-email/resend Renvoi email vérification
```

**Utilisateurs**
```
GET    /api/me                 Profil utilisateur connecté
GET    /api/user/profile       Récupération profil
PUT    /api/user/profile       Mise à jour profil
GET    /api/user               Liste users (ADMIN)
```

**Candidatures**
```
GET    /api/candidatures       Liste des candidatures
POST   /api/candidatures       Création candidature
GET    /api/candidatures/{id}  Détail candidature
PUT    /api/candidatures/{id}  Mise à jour
DELETE /api/candidatures/{id}  Suppression
```

**Entretiens**
```
GET    /api/entretiens         Liste entretiens
POST   /api/entretiens         Création entretien
PUT    /api/entretiens/{id}    Mise à jour
DELETE /api/entretiens/{id}    Suppression
```

**Recherche d'emplois**
```
GET    /api/jobs               Recherche offres (Adzuna)
```

### **Exemples de requêtes**

**Inscription**
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "Password123!",
    "firstName": "John",
    "lastName": "Doe"
  }'
```

**Connexion**
```bash
curl -X POST http://localhost:8000/api/login_check \
  -H "Content-Type: application/json" \
  -d '{
    "username": "user@example.com",
    "password": "Password123!"
  }'
```

**Récupération profil (authentifié)**
```bash
curl -X GET http://localhost:8000/api/user/profile \
  -H "Authorization: Bearer {votre_token_jwt}"
```

---

## 🧭 Évolutions possibles

### **Fonctionnalités métier**
- ✨ Dashboard avec statistiques avancées (taux de réponse, délais moyens)
- 📊 Génération de rapports PDF mensuels
- 🔔 Notifications push (entretien à venir, relance à effectuer)
- 📧 Envoi automatique des relances par email
- 🤖 Suggestions automatiques d'offres (machine learning)

### **Technique**
- 🚀 CI/CD avec GitHub Actions (tests automatiques, déploiement)
- 📈 Monitoring avec Sentry + Grafana
- 🔄 Cache Redis pour performances
- 🌐 Déploiement sur Kubernetes
- 📦 Versioning API (v1, v2)

---

## 📚 Documentation complémentaire

- **[Plan de tests](docs/PLAN_DE_TESTS_COURT.md)** - Stratégie de tests et couverture
- **[Architecture](docs/ARCHITECTURE.md)** - Diagrammes UML et choix techniques
- **[API OpenAPI](http://localhost:8000/api/docs)** - Documentation interactive Swagger
- **[Guide de déploiement](docs/DEPLOYMENT.md)** - Procédure de mise en production

---

## 👤 Auteur

**Cécile MOREL**  
Développeuse Full Stack  
Projet réalisé dans le cadre du **Titre Professionnel Concepteur Développeur d'Applications** (REAC TP-01281 v04)

---

## 📄 Licence

Ce projet est réalisé dans un cadre pédagogique (CDA).

---

## 🙏 Remerciements

- **Anthropic** pour l'assistance technique
- **Simplon** pour la formation
- **Symfony** et sa communauté pour la documentation
- **API Platform** pour l'outillage REST