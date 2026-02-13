# 📋 PLAN DE TESTS - Application FollowUp
## Concepteur Développeur d'Applications - Titre Professionnel CDA

---

## 🎯 CONTEXTE

**Application :** FollowUp - Gestionnaire de candidatures d'emploi  
**Stack technique :** Symfony 7 (PHP 8.2) + Angular 18  
**Base de données :** MySQL 8.0  
**Architecture :** API REST + JWT Authentication  

---

## 📊 SYNTHÈSE DES TESTS RÉALISÉS

### Tests Back-End (PHP Symfony)

| Type de test | Nombre | Couverture | Statut |
|--------------|--------|------------|--------|
| Tests unitaires | 5 | Services métier | ✅ 100% |
| Tests d'intégration | 12 | Endpoints API | ✅ 100% |
| Tests de sécurité | Inclus | JWT, autorisations | ✅ 100% |
| **TOTAL** | **17** | **43 assertions** | ✅ **OK** |

---

## 🧪 DÉTAIL DES TESTS UNITAIRES (5 tests)

### Fichier : `tests/Service/UserServiceTest.php`

**Objectif :** Tester la logique métier du service UserService de manière isolée

| # | Test | Ce qui est vérifié |
|---|------|-------------------|
| 1 | `test_create_should_hash_password` | Le mot de passe est bien hashé à la création |
| 2 | `test_create_should_throw_exception_if_email_exists` | Exception levée si email déjà utilisé |
| 3 | `test_create_should_throw_exception_if_email_not_gmail` | Règle métier : email doit être Gmail |
| 4 | `test_create_should_generate_verification_token` | Token de vérification généré automatiquement |
| 5 | `test_getById_should_throw_exception_if_user_not_found` | Exception levée si user inexistant |

**Approche technique :**
- Utilisation de **mocks** pour isoler les dépendances
- Pattern **AAA** (Arrange, Act, Assert)
- Framework : **PHPUnit 11.5**

---

## 🌐 DÉTAIL DES TESTS D'INTÉGRATION (12 tests)

### 1. Tests d'Inscription - `RegisterApiTest.php` (4 tests)

**Endpoint testé :** `POST /api/register`

| Test | Scénario | Code HTTP attendu |
|------|----------|-------------------|
| `test_register_with_valid_data_should_create_user` | Inscription avec données valides | 201 Created |
| `test_register_with_existing_email_should_return_409` | Email déjà utilisé | 409 Conflict |
| `test_register_with_non_gmail_email_should_return_400` | Email non Gmail | 400 Bad Request |
| `test_register_with_missing_data_should_return_400` | Données manquantes | 400 Bad Request |

**Vérifications :**
- ✅ Utilisateur créé en base de données
- ✅ Mot de passe hashé
- ✅ Token de vérification généré
- ✅ Validation des entrées

---

### 2. Tests d'Authentification JWT - `AuthApiTest.php` (4 tests)

**Endpoint testé :** `POST /api/login_check`

| Test | Scénario | Code HTTP attendu |
|------|----------|-------------------|
| `test_login_with_valid_credentials_should_return_jwt_token` | Connexion valide | 200 OK + Token JWT |
| `test_login_with_invalid_password_should_return_401` | Mot de passe incorrect | 401 Unauthorized |
| `test_login_with_non_existent_user_should_return_401` | Utilisateur inexistant | 401 Unauthorized |
| `test_login_with_missing_credentials_should_return_400` | Identifiants manquants | 400 Bad Request |

**Vérifications :**
- ✅ Token JWT retourné avec 3 parties (header.payload.signature)
- ✅ Authentification refusée avec mauvais identifiants
- ✅ Format JSON correct

---

### 3. Tests de Sécurité - `CandidatureApiTest.php` (4 tests)

**Endpoint testé :** `GET /api/my-candidatures`

| Test | Scénario | Code HTTP attendu |
|------|----------|-------------------|
| `test_authenticated_user_can_get_their_candidatures` | Accès avec token JWT valide | 200 OK |
| `test_unauthenticated_user_cannot_access_candidatures` | Accès sans token | 401 Unauthorized |
| `test_user_with_invalid_token_cannot_access_candidatures` | Token invalide | 401 Unauthorized |
| `test_user_can_only_see_their_own_candidatures` | Isolation des données | 200 OK (1 résultat) |

**Vérifications de sécurité :**
- ✅ Authentification JWT obligatoire
- ✅ Token invalide refusé
- ✅ **Isolation des données** : User A ne voit PAS les candidatures de User B
- ✅ Protection des données personnelles (RGPD)

---

## 🔒 TESTS DE SÉCURITÉ (conformes REAC)

### Vulnérabilités testées

| Vulnérabilité | Protection testée | Résultat |
|---------------|-------------------|----------|
| **Accès non autorisé** | JWT obligatoire sur routes protégées | ✅ Bloqué (401) |
| **Token JWT invalide** | Vérification signature token | ✅ Bloqué (401) |
| **Injection SQL** | Doctrine ORM + requêtes préparées | ✅ Protégé |
| **Validation entrées** | Validation Symfony (email, password) | ✅ Validé (400) |
| **Fuite de données** | Isolation user par user | ✅ Protégé |

### Conformité OWASP Top 10

- ✅ **A01:2021 - Broken Access Control** : Tests d'autorisation
- ✅ **A02:2021 - Cryptographic Failures** : Hashage bcrypt
- ✅ **A03:2021 - Injection** : ORM Doctrine
- ✅ **A07:2021 - Authentication Failures** : Tests JWT

---

## 🛠️ ENVIRONNEMENT DE TESTS

### Configuration

```yaml
Base de données de test : MySQL (followup_test)
Framework de tests : PHPUnit 11.5
Trait personnalisé : DatabasePrimer (reset BDD avant tests)
Emails : Désactivés (MAILER_DSN=null://)
```

### Commandes d'exécution

```bash
# Tous les tests
docker compose exec php ./vendor/bin/phpunit --testdox

# Tests d'intégration uniquement
docker compose exec php ./vendor/bin/phpunit tests/Api --testdox

# Tests unitaires uniquement
docker compose exec php ./vendor/bin/phpunit tests/Service --testdox
```

---

## 📈 RÉSULTATS

```
Tests: 17, Assertions: 43
Status: OK ✅
Temps d'exécution: ~11 secondes
Mémoire: 48 MB
```

---