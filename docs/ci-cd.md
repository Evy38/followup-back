# CI/CD – FollowUp Backend (Symfony)

## CI (Continuous Integration)
Objectif : vérifier automatiquement la qualité du backend à chaque PR/push vers `main`.

### Déclencheurs
- Pull Request vers `main`
- Push sur `main`

### Étapes CI
1. Checkout du code
2. Setup PHP (8.3)
3. Installation Composer
4. Préparation Symfony en environnement `test`
5. Mise en place MySQL de test
6. Création/validation du schéma Doctrine
7. Exécution des tests PHPUnit

Résultat attendu :
- pipeline vert = code acceptable pour merge

---

## CD (Continuous Deployment / Delivery)
Objectif : livrer une version déployable du backend de manière contrôlée et traçable.

### Choix retenu (CDA)
- Déclenchement manuel (workflow_dispatch)
- Déploiement simulé (pas d’infrastructure réelle)
- Environnements : SIT / UAT / PROD
- Validations possibles via GitHub Environments

### Étapes CD
1. Build backend (prod)
2. Génération d’un artefact versionné
3. Déploiement simulé vers l’environnement ciblé
4. (Prod) backup simulé + contrôles post-déploiement

Pourquoi :
- démontrer une démarche pro et sécurisée
- rendre le pipeline évolutif vers un vrai déploiement plus tard
