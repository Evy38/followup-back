# 🌍 Environnements – FollowUp Backend (Symfony)

## Vue d'ensemble
Le backend FollowUp utilise plusieurs environnements pour sécuriser les évolutions avant production.

---

## 1️⃣ DEV (Développement)

- **Accès** : développeurs
- **URL API** : `http://localhost:8080/api`
- **DB** : PostgreSQL Docker (local)
- **Mailer** : Mailhog (dev)

Objectif :
- développement et debug
- tests manuels

---

## 2️⃣ SIT (System Integration Testing)

- **Accès** : équipe dev / test
- **URL API** : `https://api-sit.followup.com` *(exemple)*
- **DB** : PostgreSQL dédiée staging *(exemple)*
- **Mailer** : désactivé ou sandbox

Objectif :
- tests d’intégration (front ↔ back)
- validation technique

---

## 3️⃣ UAT (User Acceptance Testing)

- **Accès** : validation métier / utilisateur
- **URL API** : `https://api-uat.followup.com` *(exemple)*
- **DB** : copie anonymisée ou dataset UAT *(exemple)*
- **Mailer** : sandbox

Objectif :
- validation métier (parcours utilisateurs)
- tests d’acceptation

---

## 4️⃣ PROD (Production)

- **Accès** : utilisateurs finaux
- **URL API** : `https://api.followup.com` *(exemple)*
- **DB** : PostgreSQL production
- **Mailer** : réel (SMTP/Provider)

Objectif :
- service réel en production

---

## 🔄 Flux de promotion
DEV → SIT → UAT → PROD

---

## 🔐 Variables sensibles
Les secrets (APP_SECRET, JWT_PASSPHRASE, credentials OAuth, etc.)
ne sont jamais commités : ils sont stockés dans GitHub Secrets.
