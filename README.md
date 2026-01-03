# FollowUp – Backend (Symfony) 🎯

API REST sécurisée de suivi de candidatures d’emploi  
Projet de fin de formation – **Titre Professionnel CDA**

---

## 📌 Présentation du projet

FollowUp est une application permettant à un utilisateur de **centraliser et suivre ses candidatures d’emploi** :
- entreprises contactées
- statuts des candidatures
- relances effectuées
- réponses reçues

Le backend expose une **API REST sécurisée**, conçue pour être consommée par un frontend Angular.

---

## 🎯 Objectifs pédagogiques (REAC)

Ce projet démontre ma capacité à :

- Concevoir une **API REST sécurisée**
- Mettre en place une **architecture backend claire**
- Implémenter une **authentification moderne (JWT / OAuth)**
- Séparer correctement les responsabilités (Controller / Service / Repository)
- Tester les parcours critiques de l’application
- Documenter et justifier les choix techniques

---

## 🏗️ Architecture Backend

### Séparation des responsabilités

Controller → Service → Repository → Base de données


- **Controllers**
  - Reçoivent les requêtes HTTP
  - Valident les entrées
  - Délèguent toute logique métier aux services

- **Services**
  - Contiennent la logique métier
  - Centralisent la sécurité (hash mot de passe, règles métier)
  - Rendent le code testable et maintenable

- **Repositories**
  - Gèrent exclusivement l’accès aux données (Doctrine ORM)

👉 Cette séparation respecte les bonnes pratiques Symfony et les attendus du REAC.

---

## 🔐 Sécurité & Authentification

### Authentification JWT

- Authentification via **LexikJWTAuthenticationBundle**
- API **stateless**
- Accès aux routes protégées via token JWT

### OAuth Google

- Connexion possible via Google OAuth
- Création automatique de l’utilisateur si inexistant
- Génération d’un JWT après authentification OAuth
- Stockage du token dans un **cookie HTTP-only**

👉 Le JWT n’est **jamais exposé dans l’URL**, pour éviter tout risque XSS.

### Sécurité des mots de passe

- Hash avec le **hasher Symfony**
- Jamais stockés en clair
- Politique minimale :
  - 8 caractères
  - 1 majuscule
  - 1 chiffre

---

## 🔁 Réinitialisation de mot de passe

Flux sécurisé en deux étapes :

1. Demande de réinitialisation (`/api/password/request`)
2. Génération d’un token temporaire (1h)
3. Réinitialisation avec token (`/api/password/reset`)
4. Invalidation automatique du token

✔️ Aucun retour ne révèle si un email existe ou non (protection contre l’énumération).

---

## 🧪 Tests & Qualité

### Stratégie de tests

- **Tests unitaires** :
  - Services
  - Repositories
- **Tests fonctionnels** :
  - Parcours critique de réinitialisation de mot de passe
  - Appels réels via HTTP (WebTestCase)

### Exemple de test fonctionnel

- Création utilisateur
- Demande de reset
- Récupération du token
- Réinitialisation du mot de passe
- Vérification du hash

👉 Les contrôleurs ne sont **jamais instanciés directement** dans les tests.

---

## 📦 Stack technique

| Élément | Technologie |
|---|---|
| Langage | PHP 8.2 |
| Framework | Symfony 7.3 |
| Base de données | MySQL |
| ORM | Doctrine |
| Authentification | JWT + OAuth Google |
| Tests | PHPUnit |
| Conteneurisation | Docker |

---

## 📂 Modèle de données (simplifié)

User
└── Candidature
├── Entreprise
├── Statut
├── Relance
└── Reponse


---

## 🚀 Installation (environnement local)

```bash
git clone <repo>
cd followup-back
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app php bin/console lexik:jwt:generate-keypair
docker-compose exec app php bin/console doctrine:migrations:migrate

📖 Documentation API

Swagger / OpenAPI disponible

Endpoints testables directement via l’interface

🧭 Évolutions possibles

Tests d’intégration complets

Statistiques et indicateurs de suivi

Notifications automatiques

CI/CD

👤 Auteur

Cécile
Projet réalisé dans le cadre du Titre Professionnel Concepteur Développeur d’Applications