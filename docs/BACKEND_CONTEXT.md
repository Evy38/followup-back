
# Backend – Contexte canonique du projet

## 1. Stack technique

- **Version Symfony détectée** : 7.3.*
- **API Platform** : présent (`api-platform/symfony` ^4.2)
- **ORM utilisé** : Doctrine ORM (doctrine/orm ^3.5)
- **Base de données** : MySQL (driver mysql:8.0 via Docker)
- **Authentification** : JWT (LexikJWTAuthenticationBundle), OAuth Google (présent)
- **Environnement** : Docker oui

## 2. Environnement & Docker

- **Présence de Docker** : oui (`docker-compose.yml`)
- **Services détectés** : php, db (mysql), phpmyadmin, mailhog
- **Variables d’environnement critiques** :
	- `MYSQL_ROOT_PASSWORD`, `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`, `JWT_PASSPHRASE`, `TZ`, `PMA_HOST`, `PMA_USER`, `PMA_PASSWORD`
- **Commandes clés connues** :
	- `make migration`, `make tests`, `make cache`
- **Scripts d’initialisation** : `docker/db/init.sql`

## 3. Sécurité

- **Mécanisme d’authentification exact** : JWT (LexikJWTAuthenticationBundle), login via `/api/login_check`, OAuth Google via `/auth/google`
- **Bundles de sécurité utilisés** : SecurityBundle, LexikJWTAuthenticationBundle
- **Firewalls configurés** : dev, swagger, login, api (JWT obligatoire sur `/api/**`)
- **Rôles existants** : `ROLE_USER`, `ROLE_ADMIN`
- **Protection des routes** :
	- `access_control` (ex : `/api/register` PUBLIC_ACCESS)
	- Attributs `#[IsGranted]` dans les contrôleurs
	- Expressions security dans `ApiResource`
- **Gestion du token** : émission via login, validation JWT, TTL 3600s
- **OAuth** : Google (routes `/auth/google`, `/auth/google/callback`), gestion du token dans AuthController

## 4. Architecture backend

- **Controller/** : Contrôleurs API REST, Auth, User, Job, Me, Candidature, etc.
- **Entity/** : Entités Doctrine (`User`, `Candidature`, `Entreprise`, `Relance`, `Statut`, `Canal`, `MotCle`, `Ville`)
- **Repository/** : Repositories Doctrine pour chaque entité
- **DTO/** : Objets de transfert (`CreateCandidatureFromOfferDTO`, `JobOfferDTO`)
- **Service/** : Services métier (`RelanceService`, `UserService`, `EmailVerificationService`, `GoogleAuthService`, `OAuthUserService`)
- **State/** : Processor API Platform (`RelanceUpdateProcessor`)
- **Security/** : NON DÉTECTÉ

## 5. Modèle de données (entités)

- **User** : id, email, firstName, lastName, googleId, roles, password, isVerified, emailVerificationToken, candidatures (OneToMany)
- **Candidature** : id, dateCandidature, jobTitle, lienAnnonce, user (ManyToOne), entreprise (ManyToOne), statut (ManyToOne), relances (OneToMany), motsCles (ManyToMany)
- **Entreprise** : id, nom, secteur, siteWeb, candidatures (OneToMany)
- **Relance** : id, dateRelance, type, commentaire, candidature (ManyToOne), motsCles (ManyToMany)
- **Statut** : id, libelle, candidatures (OneToMany)
- **Canal** : id, libelle
- **MotCle** : id, libelle, relances (ManyToMany), candidatures (ManyToMany)
- **Ville** : id, nomVille, codePostal, pays

## 6. API / Routes effectives

### API Platform

- **Ressources exposées** : User, Candidature, Entreprise, Relance, Statut, Canal, MotCle, Ville
- **Mode d’exposition réel** :
	- Opérations par défaut et custom (Get, GetCollection, Post, Put, Delete, Patch)
	- Sécurité par opération (expressions security, securityPostDenormalize)
	- Processors utilisés : `RelanceUpdateProcessor` (Relance)
	- Providers : NON DÉTECTÉ

### Contrôleurs custom

| Méthode | URL | Contrôleur::méthode | Sécurité | Objectif métier |
|---------|-----|---------------------|----------|----------------|
| POST    | `/api/candidatures/from-offer` | CandidatureFromOfferController::createFromOffer | ROLE_USER | Création candidature depuis offre externe |
| PATCH   | `/api/candidatures/{id}/statut-reponse` | CandidatureReponseController::updateStatutReponse | ROLE_USER | Mise à jour du statut de réponse |
| PATCH   | `/api/candidatures/{id}/entretien` | CandidatureReponseController::updateEntretien | ROLE_USER | Mise à jour entretien |
| GET     | `/api/my-candidatures` | MyCandidaturesController::index | ROLE_USER | Liste des candidatures de l’utilisateur |
| GET     | `/api/user/profile` | UserController::getProfile | - | Profil utilisateur connecté |
| PUT     | `/api/user/profile` | UserController::updateProfile | - | Modification profil |
| GET     | `/api/user` | UserController::list | ROLE_ADMIN | Liste des utilisateurs |
| POST    | `/api/register` | RegisterController::register | - | Création de compte |
| POST    | `/api/password/request` | ForgotPasswordController::requestPasswordReset | - | Demande reset mot de passe |
| POST    | `/api/password/reset` | ForgotPasswordController::resetPassword | - | Reset mot de passe |
| GET     | `/api/verify-email` | VerifyEmailController::verifyEmail | - | Vérification email |
| POST    | `/api/verify-email/resend` | VerifyEmailController::resendVerificationEmail | - | Renvoi email vérification |
| GET     | `/auth/google` | AuthController::google | - | OAuth Google |
| GET     | `/auth/google/callback` | AuthController::googleCallback | - | OAuth Google callback |

## 7. Vérification des droits & ownership

- **Vérification d’ownership** :
	- API Platform : expressions security sur entités (ex : `object.getUser() == user`)
	- Contrôleurs custom : vérification explicite dans le code (ex : `$candidature->getUser() !== $this->getUser()`)
	- Services : NON DÉTECTÉ
- **Hiérarchie réelle** : contrôle d’accès d’abord via firewall/access_control, puis vérification fine dans les contrôleurs et entités

## 8. CRUD effectif (synthèse)

- **User** : Create (register), Read (profile, list), Update (profile), Delete : NON DÉTECTÉ
- **Candidature** : CRUD complet, ownership strict
- **Entreprise** : Read (Get, GetCollection), Create/Update/Delete : NON DÉTECTÉ
- **Relance** : CRUD complet, ownership strict, processors custom
- **Statut, Canal, MotCle, Ville** : principalement référentiels, CRUD partiel selon rôle

> **Synthèse** :
> - Entités cœur métier = User, Candidature, Relance
> - Entités principalement référentielles = Statut, Canal, MotCle, Ville, Entreprise

## 9. JSON renvoyés & sérialisation

- **Groupes de sérialisation utilisés** : `@Groups` sur entités, contextes `normalizationContext`/`denormalizationContext`
- **Présence d’IRI** : oui (API Platform)
- **Objets imbriqués** : oui (relations Doctrine exposées)
- **Différences entre endpoints API Platform et custom** : custom => `JsonResponse` explicite, API Platform => Hydra/JSON-LD

## 10. Gestion des erreurs API

- **Validation des données** : Symfony Validator, contraintes sur entités, validation explicite dans contrôleurs
- **Format des erreurs** : Hydra (API Platform), `JsonResponse` (custom), `HttpException`
- **Différences** : API Platform = Hydra, custom = tableau JSON simple

## 11. Transactions & cohérence métier

- **Usage de transactions explicites** : NON DÉTECTÉ
- **Orchestration multi-entités via services** : oui (ex : création candidature, OAuth)
- **Dépendance à Doctrine implicitement** : oui

## 12. Services externes & intégrations

- **Services externes utilisés** : Google OAuth (`GoogleAuthService`, `OAuthUserService`), Mailhog (test email)
- **Services dédiés** : `EmailVerificationService`, `UserService`, `RelanceService`
- **Impact sur le cœur métier** : gestion login, vérification email, relances

## 13. Flux métier critiques

- **Login → JWT** : `/api/login_check` (LexikJWT), firewall login
- **Inscription → vérification email** : `RegisterController` + `EmailVerificationService` + `VerifyEmailController`
- **OAuth Google** : `AuthController` (`/auth/google`, `/auth/google/callback`), `GoogleAuthService`, `OAuthUserService`
- **Création candidature depuis offre** : `CandidatureFromOfferController::createFromOffer`, validation, création entités, services

## 14. Points sensibles / pièges connus

- **Mélange IRI / objets** : visible dans les retours API Platform vs custom
- **Couplages forts** : entités fortement liées (`User`, `Candidature`, `Relance`)
- **Hypothèses implicites** : ownership strict sur `Candidature`/`Relance`, absence de transaction explicite

---

Ce document reflète l’état réel du backend, sans extrapolation ni invention.

