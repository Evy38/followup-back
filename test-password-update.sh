#!/bin/bash

BASE_URL="http://localhost:8080"

# ==========================
# USER
# ==========================

USER_EMAIL="amalriccecile@gmail.com"
USER_PASSWORD="testtest123"

# ==========================
# ADMIN
# ==========================

ADMIN_EMAIL="cecilemorel0907@gmail.com"
ADMIN_PASSWORD="admin123"

echo "=============================="
echo "1️⃣ Connexion USER"
echo "=============================="

USER_LOGIN=$(curl -s -X POST $BASE_URL/api/login_check \
  -H "Content-Type: application/json" \
  -d "{
        \"email\":\"$USER_EMAIL\",
        \"password\":\"$USER_PASSWORD\"
      }")

USER_TOKEN=$(echo $USER_LOGIN | sed -n 's/.*"token":"\([^"]*\)".*/\1/p')

if [ -z "$USER_TOKEN" ]; then
  echo "❌ Connexion USER échouée"
  echo $USER_LOGIN
  exit 1
fi

echo "✅ USER connecté"
echo ""

echo "=============================="
echo "2️⃣ USER supprime son compte"
echo "=============================="

curl -s -X DELETE $BASE_URL/api/user/profile \
  -H "Authorization: Bearer $USER_TOKEN"

echo ""
echo ""
echo "=============================="
echo "3️⃣ USER tente d'utiliser son ancien JWT"
echo "=============================="

curl -s -X GET $BASE_URL/api/me \
  -H "Authorization: Bearer $USER_TOKEN"

echo ""
echo ""
echo "=============================="
echo "4️⃣ Connexion ADMIN"
echo "=============================="

ADMIN_LOGIN=$(curl -s -X POST $BASE_URL/api/login_check \
  -H "Content-Type: application/json" \
  -d "{
        \"email\":\"$ADMIN_EMAIL\",
        \"password\":\"$ADMIN_PASSWORD\"
      }")

ADMIN_TOKEN=$(echo $ADMIN_LOGIN | sed -n 's/.*"token":"\([^"]*\)".*/\1/p')

if [ -z "$ADMIN_TOKEN" ]; then
  echo "❌ Connexion ADMIN échouée"
  echo $ADMIN_LOGIN
  exit 1
fi

echo "✅ ADMIN connecté"
echo ""

echo "=============================="
echo "5️⃣ ADMIN accède au dashboard"
echo "=============================="

curl -s -X GET $BASE_URL/api/admin/dashboard \
  -H "Authorization: Bearer $ADMIN_TOKEN"

echo ""
echo ""
echo "=============================="
echo "FIN DES TESTS"
echo "=============================="
