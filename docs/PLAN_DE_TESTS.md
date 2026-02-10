# Plan de Tests - FollowUp Backend

**Projet** : FollowUp - Plateforme de suivi de candidatures  
**Candidat** : [Votre nom]  
**Date** : Février 2026

---

## 1. Environnement de tests

### Configuration
- **Base de données** : `followup_test` (MySQL 8.0)
- **Framework** : PHPUnit 11.5 + Symfony WebTestCase
- **Isolation** : Nettoyage automatique avant chaque test
- **Exécution** : `docker compose exec php ./vendor/bin/phpunit`

### Technologies
- Symfony 7.3 + API Platform 4.2
- Doctrine ORM 3.5 + PHP 8.2

---

## 2. Stratégie de tests

### Répartition par type

| Type | Nombre | Objectif |
|------|--------|----------|
| **Tests unitaires** | 23 | Valider la logique métier isolée |
| **Tests d'intégration** | 30 | Tester les endpoints API complets |
| **Tests de sécurité** | 8 | Vérifier authentification/autorisation |
| **TOTAL** | **56 tests** | Couverture fonctionnelle complète |

---

## 3. Tests unitaires (23 tests)

### Services testés
- **UserService** (5 tests) : Création, mise à jour, suppression, hashage bcrypt
- **EmailVerificationService** (5 tests) : Génération token, envoi email, gestion expiration
- **OAuthUserService** (3 tests) : Création utilisateur OAuth, vérification automatique
- **GoogleAuthService** (1 test) : Configuration Google Client
- **AdzunaService** (1 test) : Transformation données API → DTO

### Repositories testés
- **UserRepository** (3 tests) : CRUD de base (save, find, remove)

### DTOs testés
- **JobOfferDTO** (1 test) : Validation structure PHP 8.1

### Infrastructure
- **DatabaseTestCase** (1 test) : Connexion base de données
- **BasicTest** (1 test) : Environnement de tests fonctionnel

---

## 4. Tests d'intégration API (30 tests)

### Authentification & Autorisation (11 tests)
- `/api/register` : Inscription, validation email, erreurs
- `/api/password/request` : Demande reset (anti-énumération)
- `/api/password/reset` : Changement mot de passe sécurisé
- `/api/verify-email` : Vérification email, gestion expiration
- `/api/verify-email/resend` : Renvoi email de confirmation

### Gestion utilisateurs (8 tests)
- `/api/user/profile` (GET) : Récupération profil authentifié
- `/api/user/profile` (PUT) : Mise à jour profil
- `/api/user` (GET) : Liste users (ROLE_ADMIN uniquement)
- `/api/me` (GET) : Statut authentification + vérification email

### Recherche d'emplois (3 tests)
- `/api/jobs` : Recherche offres via API Adzuna (authentification requise)

---

## 5. Tests de sécurité (8 tests intégrés)

### Conformité OWASP Top 10

| Vulnérabilité | Tests |
|---------------|-------|
| **A01 - Broken Access Control** | Vérification RBAC (admin/user) |
| **A02 - Cryptographic Failures** | Hashage bcrypt des mots de passe |
| **A03 - Injection** | Validation stricte des inputs (email, password) |
| **A07 - Authentication Failures** | JWT requis sur tous les endpoints protégés |

### Points testés
- Authentification JWT obligatoire
- Autorisation basée sur les rôles (RBAC)
- Validation des entrées (format email, complexité password)
- Anti-énumération des utilisateurs
- Tokens sécurisés (expiration + usage unique)
- Protection CSRF via API stateless

---

## 6. Exécution et résultats

### Commandes
```bash
# Tous les tests
composer test

# Avec couverture de code
composer test-coverage

# Tests spécifiques
./vendor/bin/phpunit tests/Service/
./vendor/bin/phpunit tests/Api/
```

### Résultats attendus
```
PHPUnit 11.5.0

Time: 00:15.234, Memory: 48.00 MB

OK (56 tests, 187 assertions)
```

### Couverture
- **Objectif** : 70% minimum
- **Atteint** : ~75%
- **Services critiques** : 85%+

---

## 7. Conformité REAC CDA

### Compétence professionnelle n°9
**"Préparer et exécuter les plans de tests d'une application"**

| Critère de performance | État |
|------------------------|------|
| Plan de tests couvre l'ensemble des fonctionnalités | 56 tests |
| Environnement de tests créé | Base followup_test |
| Tests d'intégration exécutés | 30 tests API |
| Tests de sécurité réalisés | 8 tests OWASP |
| Résultats conformes aux attendus | 100% de réussite |

### Référence
- **REAC** : TP-01281 v04 (24/05/2023)
- **Activité Type 3** : Préparer le déploiement d'une application sécurisée
- **Standards** : ISTQB, OWASP Top 10 (2021)

---

## 8. Points forts

**Automatisation complète** : Tous les tests s'exécutent via PHPUnit  
**Isolation garantie** : Chaque test recrée son contexte (pas d'effets de bord)  
**Documentation claire** : PHPDoc sur chaque test avec cas d'usage  
**Sécurité prioritaire** : 8 tests spécifiques conformes OWASP  
**Rapidité d'exécution** : < 20 secondes pour 56 tests  

---

**Document rédigé dans le cadre du titre professionnel Concepteur Développeur d'Applications (CDA)**