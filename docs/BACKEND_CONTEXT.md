

# Backend – Contexte projet (état réel)

## 1. Stack technique

- **Symfony** : 7.3.x
- **API Platform** : 4.x (ressources exposées, sécurité fine)
- **Doctrine ORM** : 3.x
- **Base de données** : MySQL 8 (via Docker)
- **Authentification** : JWT (LexikJWTAuthenticationBundle), OAuth Google
- **Environnement** : Docker (php, mysql, phpmyadmin, mailhog)

## 2. Environnement & Docker

- **Docker** : oui (`docker-compose.yml`)
- **Services** : php, db (mysql), phpmyadmin, mailhog
- **Variables d’environnement** : `MYSQL_ROOT_PASSWORD`, `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`, `JWT_PASSPHRASE`, etc.
- **Commandes clés** : `make migration`, `make tests`, `make cache`

## 3. Sécurité

- **Authentification** : JWT (login `/api/login_check`), OAuth Google (`/auth/google`)
- **Bundles** : SecurityBundle, LexikJWTAuthenticationBundle
- **Firewalls** : dev, swagger, login, api (JWT obligatoire sur `/api/**`)
- **Rôles** : `ROLE_USER`, `ROLE_ADMIN`
- **Protection des routes** :
  - `access_control` (ex : `/api/register` PUBLIC_ACCESS)
  - Attributs `#[IsGranted]` dans les contrôleurs
  - Expressions security dans `ApiResource`
- **Gestion du token** : émission via login, validation JWT, TTL 3600s
- **OAuth** : Google (routes `/auth/google`, `/auth/google/callback`)

## 4. Architecture backend

- **src/Controller/Api/** : Contrôleurs API REST (Admin, Candidature, Me, Job, etc.)
- **src/Controller/Auth/** : Auth, Register, ForgotPassword, VerifyEmail
- **src/Entity/** : User, Candidature, Entreprise, Relance, Statut, Entretien
- **src/Repository/** : User, Candidature, Entreprise
- **src/DTO/** : CreateCandidatureFromOfferDTO, JobOfferDTO
- **src/Service/** : RelanceService, UserService, EmailVerificationService, GoogleAuthService, OAuthUserService, AdzunaService, CandidatureStatutSyncService
- **src/State/** : RelanceUpdateProcessor, EntretienProcessor

## 5. Modèle de données (entités principales)

- **User** : id, email, firstName, lastName, googleId, roles, password, isVerified, emailVerificationToken, resetPasswordToken, candidatures (OneToMany)
- **Candidature** : id, dateCandidature, jobTitle, lienAnnonce, user (ManyToOne), entreprise (ManyToOne), statut (ManyToOne), relances (OneToMany), entretiens (OneToMany), externalOfferId, mode, statutReponse
- **Entreprise** : id, nom, candidatures (OneToMany)
- **Relance** : id, dateRelance, type, commentaire, candidature (ManyToOne), rang, faite, dateRealisation
- **Statut** : id, libelle, candidatures (OneToMany)
- **Entretien** : id, dateEntretien, heureEntretien, statut, resultat, commentaire, candidature (ManyToOne)

## 6. API / Routes effectives

### API Platform

- **Ressources exposées** : User, Candidature, Entreprise, Relance, Statut, Entretien
- **Opérations** :
  - Get, GetCollection, Post, Put, Delete, Patch (selon entité)
  - Sécurité par opération (expressions security, securityPostDenormalize)
  - Processors : RelanceUpdateProcessor (Relance), EntretienProcessor (Entretien)

### Contrôleurs custom principaux

| Méthode | URL | Contrôleur::méthode | Sécurité | Objectif |
|---------|-----|---------------------|----------|----------|
| POST    | `/api/candidatures/from-offer` | CandidatureFromOfferController::createFromOffer | ROLE_USER | Création candidature depuis offre externe |
| PATCH   | `/api/candidatures/{id}/statut-reponse` | CandidatureReponseController::updateStatutReponse | ROLE_USER | Mise à jour du statut de réponse |
| GET     | `/api/my-candidatures` | MyCandidaturesController::index | ROLE_USER | Liste des candidatures de l’utilisateur |
| GET     | `/api/me` | MeController::me | ROLE_USER | Infos utilisateur connecté |
| GET     | `/api/jobs` | JobController::search | - | Recherche offres externes |
| GET     | `/api/admin/dashboard` | AdminController::dashboard | ROLE_ADMIN | Dashboard admin |
| POST    | `/api/register` | RegisterController::register | - | Création de compte |
| POST    | `/api/password/request` | ForgotPasswordController::requestPasswordReset | - | Demande reset mot de passe |
| GET     | `/api/verify-email` | VerifyEmailController::verifyEmail | - | Vérification email |
| GET     | `/auth/google` | AuthController::google | - | OAuth Google |
| GET     | `/auth/google/callback` | AuthController::googleCallback | - | OAuth Google callback |

## 7. Vérification des droits & ownership

- **API Platform** : expressions security sur entités (ex : `object.getUser() == user`)
- **Contrôleurs custom** : vérification explicite dans le code (ex : `$candidature->getUser() !== $this->getUser()`)
- **Hiérarchie** : firewall/access_control puis vérification fine dans les contrôleurs et entités

## 8. CRUD effectif (synthèse)

- **User** : Create (register), Read (me, list), Update (NON exposé), Delete (NON exposé)
- **Candidature** : CRUD complet, ownership strict
- **Entreprise** : Read (Get, GetCollection), Create/Update/Delete : NON exposé
- **Relance** : CRUD complet, ownership strict, processors custom
- **Statut** : Read (Get, GetCollection)
- **Entretien** : CRUD via API Platform, ownership strict, processor custom

## 9. Sérialisation & retours JSON

- **Groupes de sérialisation** : `@Groups` sur entités, contextes `normalizationContext`/`denormalizationContext`
- **Présence d’IRI** : oui (API Platform)
- **Objets imbriqués** : oui (relations Doctrine exposées)
- **Endpoints custom** : `JsonResponse` explicite, API Platform : Hydra/JSON-LD

## 10. Gestion des erreurs API

- **Validation** : Symfony Validator, contraintes sur entités, validation explicite dans contrôleurs
- **Format des erreurs** : Hydra (API Platform), `JsonResponse` (custom), `HttpException`

## 11. Transactions & cohérence métier

- **Transactions explicites** : NON détecté
- **Orchestration multi-entités via services** : oui (création candidature, OAuth, etc.)
- **Dépendance à Doctrine** : oui

## 12. Services externes & intégrations

- **Google OAuth** : GoogleAuthService, OAuthUserService
- **Adzuna** : AdzunaService (recherche offres)
- **Mailhog** : test email
- **Services dédiés** : EmailVerificationService, UserService, RelanceService

## 13. Flux métier critiques

- **Login → JWT** : `/api/login_check` (LexikJWT)
- **Inscription → vérification email** : RegisterController + EmailVerificationService + VerifyEmailController
- **OAuth Google** : AuthController (`/auth/google`, `/auth/google/callback`), GoogleAuthService, OAuthUserService
- **Création candidature depuis offre** : CandidatureFromOfferController::createFromOffer, validation, création entités, services

## 14. Points sensibles / pièges connus

- **Mélange IRI / objets** : API Platform vs custom
- **Couplages forts** : entités User, Candidature, Relance
- **Ownership strict** : sur Candidature/Relance, pas de transaction explicite

---

Ce document reflète l’état réel du backend, sans extrapolation ni invention.

