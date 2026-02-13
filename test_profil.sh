#!/bin/bash

# =============================================================================
# Script de test DÉTAILLÉ pour la création de candidature - FollowUp API
# =============================================================================
# Usage: ./test_create_candidature.sh
# =============================================================================

set -e

# Configuration
API_BASE_URL="http://localhost:8080"
USER_EMAIL="amalriccecile@gmail.com"
USER_PASSWORD="testtest123"

# Couleurs
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

JWT_TOKEN=""

# =============================================================================
# Fonctions utilitaires
# =============================================================================

print_header() {
    echo ""
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ️  $1${NC}"
}

print_request() {
    echo -e "${YELLOW}📤 REQUEST: $1 $2${NC}"
    if [ ! -z "$3" ]; then
        echo -e "${YELLOW}📦 BODY:${NC}"
        echo "$3" | jq '.' 2>/dev/null || echo "$3"
    fi
}

print_response() {
    echo -e "${GREEN}📥 RESPONSE (HTTP $1):${NC}"
    echo "$2" | jq '.' 2>/dev/null || echo "$2"
    echo ""
}

# =============================================================================
# 1️⃣ Authentification
# =============================================================================

authenticate() {
    print_header "1️⃣  AUTHENTIFICATION"
    
    PAYLOAD="{\"email\": \"$USER_EMAIL\", \"password\": \"$USER_PASSWORD\"}"
    print_request "POST" "/api/login_check" "$PAYLOAD"
    
    RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$API_BASE_URL/api/login_check" \
        -H "Content-Type: application/json" \
        -d "$PAYLOAD")
    
    HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
    BODY=$(echo "$RESPONSE" | sed '$d')
    
    if [ "$HTTP_CODE" != "200" ]; then
        print_error "Échec de l'authentification (HTTP $HTTP_CODE)"
        print_response "$HTTP_CODE" "$BODY"
        exit 1
    fi
    
    JWT_TOKEN=$(echo $BODY | jq -r '.token // empty')
    
    if [ -z "$JWT_TOKEN" ]; then
        print_error "Token JWT non trouvé dans la réponse"
        print_response "$HTTP_CODE" "$BODY"
        exit 1
    fi
    
    print_success "Authentification réussie"
    print_info "Token JWT : ${JWT_TOKEN:0:50}..."
    echo ""
}

# =============================================================================
# 2️⃣ Vérification des statuts
# =============================================================================

check_statuts() {
    print_header "2️⃣  VÉRIFICATION DES STATUTS EN BASE"
    
    print_request "GET" "/api/statuts"
    
    RESPONSE=$(curl -s -w "\n%{http_code}" -X GET "$API_BASE_URL/api/statuts" \
        -H "Authorization: Bearer $JWT_TOKEN")
    
    HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
    BODY=$(echo "$RESPONSE" | sed '$d')
    
    if [ "$HTTP_CODE" != "200" ]; then
        print_error "Échec de récupération des statuts (HTTP $HTTP_CODE)"
        print_response "$HTTP_CODE" "$BODY"
        exit 1
    fi
    
    STATUT_COUNT=$(echo "$BODY" | jq '."hydra:member" | length')
    
    if [ "$STATUT_COUNT" -eq "0" ]; then
        print_error "Aucun statut trouvé en base !"
        print_info "Exécutez : php bin/console doctrine:fixtures:load"
        exit 1
    fi
    
    print_success "Statuts trouvés : $STATUT_COUNT"
    print_response "$HTTP_CODE" "$BODY"
    
    # Vérification spécifique du statut "Envoyée"
    HAS_ENVOYEE=$(echo "$BODY" | jq '."hydra:member"[] | select(.libelle == "Envoyée") | .libelle' | wc -l)
    
    if [ "$HAS_ENVOYEE" -eq "0" ]; then
        print_error "Le statut 'Envoyée' est manquant !"
        print_info "Exécutez : php bin/console doctrine:fixtures:load"
        exit 1
    fi
    
    print_success "Le statut 'Envoyée' existe bien ✅"
}

# =============================================================================
# 3️⃣ Test création candidature - Cas VALIDE
# =============================================================================

test_create_valid() {
    print_header "3️⃣  TEST CRÉATION CANDIDATURE - CAS VALIDE"
    
    TIMESTAMP=$(date +%s)
    PAYLOAD=$(cat <<EOF
{
    "externalId": "test-offer-$TIMESTAMP",
    "company": "Test Company SAS",
    "redirectUrl": "https://example.com/jobs/test-$TIMESTAMP",
    "title": "Développeur PHP Symfony - Test",
    "location": "Lyon, Rhône"
}
EOF
)
    
    print_request "POST" "/api/candidatures/from-offer" "$PAYLOAD"
    
    RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$API_BASE_URL/api/candidatures/from-offer" \
        -H "Authorization: Bearer $JWT_TOKEN" \
        -H "Content-Type: application/json" \
        -d "$PAYLOAD")
    
    HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
    BODY=$(echo "$RESPONSE" | sed '$d')
    
    if [ "$HTTP_CODE" == "201" ]; then
        print_success "Candidature créée avec succès ! 🎉"
        print_response "$HTTP_CODE" "$BODY"
        
        CANDIDATURE_ID=$(echo "$BODY" | jq -r '.id // empty')
        print_success "ID de la candidature créée : $CANDIDATURE_ID"
        
        # Vérification des relances générées automatiquement
        RELANCES_COUNT=$(echo "$BODY" | jq '.relances | length')
        print_success "Relances générées automatiquement : $RELANCES_COUNT"
        
        if [ "$RELANCES_COUNT" == "3" ]; then
            print_success "Les 3 relances ont bien été générées (J+7, J+14, J+21) ✅"
        else
            print_error "Nombre de relances incorrect : attendu 3, obtenu $RELANCES_COUNT"
        fi
        
    else
        print_error "Échec de création (HTTP $HTTP_CODE)"
        print_response "$HTTP_CODE" "$BODY"
        
        # Affichage de l'erreur détaillée si disponible
        ERROR_MSG=$(echo "$BODY" | jq -r '.message // .detail // "Erreur inconnue"')
        print_error "Message d'erreur : $ERROR_MSG"
    fi
}

# =============================================================================
# 4️⃣ Test création candidature - DOUBLON (doit retourner l'existant)
# =============================================================================

test_create_duplicate() {
    print_header "4️⃣  TEST CRÉATION CANDIDATURE - DOUBLON"
    
    TIMESTAMP=$(date +%s)
    PAYLOAD=$(cat <<EOF
{
    "externalId": "duplicate-test-$TIMESTAMP",
    "company": "Duplicate Company",
    "redirectUrl": "https://example.com/jobs/duplicate-$TIMESTAMP",
    "title": "Poste Test Doublon",
    "location": "Paris, France"
}
EOF
)
    
    print_info "Création de la première candidature..."
    print_request "POST" "/api/candidatures/from-offer" "$PAYLOAD"
    
    RESPONSE1=$(curl -s -w "\n%{http_code}" -X POST "$API_BASE_URL/api/candidatures/from-offer" \
        -H "Authorization: Bearer $JWT_TOKEN" \
        -H "Content-Type: application/json" \
        -d "$PAYLOAD")
    
    HTTP_CODE1=$(echo "$RESPONSE1" | tail -n1)
    BODY1=$(echo "$RESPONSE1" | sed '$d')
    
    if [ "$HTTP_CODE1" == "201" ]; then
        print_success "Première candidature créée"
        CANDIDATURE_ID_1=$(echo "$BODY1" | jq -r '.id')
        print_info "ID : $CANDIDATURE_ID_1"
    else
        print_error "Échec de création de la première candidature"
        print_response "$HTTP_CODE1" "$BODY1"
        return
    fi
    
    echo ""
    print_info "Tentative de création d'un doublon (même redirectUrl)..."
    print_request "POST" "/api/candidatures/from-offer" "$PAYLOAD"
    
    RESPONSE2=$(curl -s -w "\n%{http_code}" -X POST "$API_BASE_URL/api/candidatures/from-offer" \
        -H "Authorization: Bearer $JWT_TOKEN" \
        -H "Content-Type: application/json" \
        -d "$PAYLOAD")
    
    HTTP_CODE2=$(echo "$RESPONSE2" | tail -n1)
    BODY2=$(echo "$RESPONSE2" | sed '$d')
    
    if [ "$HTTP_CODE2" == "200" ]; then
        CANDIDATURE_ID_2=$(echo "$BODY2" | jq -r '.id')
        
        if [ "$CANDIDATURE_ID_1" == "$CANDIDATURE_ID_2" ]; then
            print_success "Doublon détecté : l'existante a été retournée ✅"
            print_info "ID retourné : $CANDIDATURE_ID_2 (identique)"
        else
            print_error "Les IDs diffèrent : doublon non détecté !"
            print_info "ID 1 : $CANDIDATURE_ID_1"
            print_info "ID 2 : $CANDIDATURE_ID_2"
        fi
    else
        print_error "Erreur lors de la tentative de doublon (HTTP $HTTP_CODE2)"
        print_response "$HTTP_CODE2" "$BODY2"
    fi
}

# =============================================================================
# 5️⃣ Test création candidature - DONNÉES INVALIDES
# =============================================================================

test_create_invalid() {
    print_header "5️⃣  TEST CRÉATION CANDIDATURE - DONNÉES INVALIDES"
    
    # Test 1 : externalId manquant
    print_info "Test 1 : externalId manquant"
    PAYLOAD1='{"company": "Test", "redirectUrl": "https://test.com", "title": "Test"}'
    print_request "POST" "/api/candidatures/from-offer" "$PAYLOAD1"
    
    RESPONSE1=$(curl -s -w "\n%{http_code}" -X POST "$API_BASE_URL/api/candidatures/from-offer" \
        -H "Authorization: Bearer $JWT_TOKEN" \
        -H "Content-Type: application/json" \
        -d "$PAYLOAD1")
    
    HTTP_CODE1=$(echo "$RESPONSE1" | tail -n1)
    BODY1=$(echo "$RESPONSE1" | sed '$d')
    
    if [ "$HTTP_CODE1" == "422" ] || [ "$HTTP_CODE1" == "400" ]; then
        print_success "Validation échouée comme attendu (HTTP $HTTP_CODE1)"
        ERROR_MSG=$(echo "$BODY1" | jq -r '.errors.externalId[0] // .message // "Erreur de validation"')
        print_info "Erreur : $ERROR_MSG"
    else
        print_error "Devrait retourner 422 ou 400, obtenu $HTTP_CODE1"
    fi
    
    echo ""
    
    # Test 2 : redirectUrl invalide
    print_info "Test 2 : redirectUrl invalide (pas une URL)"
    PAYLOAD2='{"externalId": "test", "company": "Test", "redirectUrl": "pas-une-url", "title": "Test"}'
    print_request "POST" "/api/candidatures/from-offer" "$PAYLOAD2"
    
    RESPONSE2=$(curl -s -w "\n%{http_code}" -X POST "$API_BASE_URL/api/candidatures/from-offer" \
        -H "Authorization: Bearer $JWT_TOKEN" \
        -H "Content-Type: application/json" \
        -d "$PAYLOAD2")
    
    HTTP_CODE2=$(echo "$RESPONSE2" | tail -n1)
    BODY2=$(echo "$RESPONSE2" | sed '$d')
    
    if [ "$HTTP_CODE2" == "422" ] || [ "$HTTP_CODE2" == "400" ]; then
        print_success "Validation échouée comme attendu (HTTP $HTTP_CODE2)"
        ERROR_MSG=$(echo "$BODY2" | jq -r '.errors.redirectUrl[0] // .message // "Erreur de validation"')
        print_info "Erreur : $ERROR_MSG"
    else
        print_error "Devrait retourner 422 ou 400, obtenu $HTTP_CODE2"
    fi
}

# =============================================================================
# 6️⃣ Test création candidature - DONNÉES ADZUNA RÉELLES
# =============================================================================

test_create_adzuna_format() {
    print_header "6️⃣  TEST AVEC FORMAT ADZUNA RÉEL"
    
    TIMESTAMP=$(date +%s)
    PAYLOAD=$(cat <<EOF
{
    "externalId": "4529804735",
    "company": "LINKIAA CONSULTING",
    "redirectUrl": "https://www.adzuna.fr/land/ad/4529804735?se=fqrLKm58v0G9JG7PJ7B0Sw&utm_medium=api&utm_source=3b48fab2&v=047C69BAF17F072BD3AA95E40F48A087E77B42CD",
    "title": "Business Developer H/F",
    "location": "Lyon, Rhône"
}
EOF
)
    
    print_request "POST" "/api/candidatures/from-offer" "$PAYLOAD"
    
    RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$API_BASE_URL/api/candidatures/from-offer" \
        -H "Authorization: Bearer $JWT_TOKEN" \
        -H "Content-Type: application/json" \
        -d "$PAYLOAD")
    
    HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
    BODY=$(echo "$RESPONSE" | sed '$d')
    
    if [ "$HTTP_CODE" == "201" ] || [ "$HTTP_CODE" == "200" ]; then
        print_success "Candidature créée avec format Adzuna ✅"
        print_response "$HTTP_CODE" "$BODY"
        
        # Vérification des champs
        COMPANY=$(echo "$BODY" | jq -r '.entreprise.nom')
        TITLE=$(echo "$BODY" | jq -r '.jobTitle')
        STATUT=$(echo "$BODY" | jq -r '.statut.libelle')
        
        print_info "Entreprise : $COMPANY"
        print_info "Titre du poste : $TITLE"
        print_info "Statut : $STATUT"
        
    else
        print_error "Échec de création avec format Adzuna (HTTP $HTTP_CODE)"
        print_response "$HTTP_CODE" "$BODY"
    fi
}

# =============================================================================
# 🎯 EXÉCUTION DES TESTS
# =============================================================================

main() {
    print_header "🚀 TESTS DE CRÉATION DE CANDIDATURE"
    
    # Vérification de jq
    if ! command -v jq &> /dev/null; then
        print_error "jq n'est pas installé"
        echo "Installation : sudo apt install jq (Linux) ou brew install jq (Mac)"
        exit 1
    fi
    
    # Authentification
    authenticate
    
    # Vérification des prérequis
    check_statuts
    
    # Tests de création
    test_create_valid
    test_create_duplicate
    test_create_invalid
    test_create_adzuna_format
    
    print_header "✅ TESTS TERMINÉS"
    
    echo ""
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}📊 RÉSUMÉ${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo -e "${BLUE}✅ Tests réussis${NC}"
    echo -e "${BLUE}❌ Tests échoués (vérifier les logs ci-dessus)${NC}"
    echo ""
    echo -e "${YELLOW}💡 Prochaines étapes :${NC}"
    echo "1. Vérifier les candidatures créées : GET /api/my-candidatures"
    echo "2. Tester la mise à jour du statut de réponse : PATCH /api/candidatures/{id}/statut-reponse"
    echo "3. Tester la création d'entretiens : POST /api/entretiens"
    echo ""
}

# Lancement
main