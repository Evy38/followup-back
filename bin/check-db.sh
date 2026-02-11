#!/bin/bash
echo "🔍 Vérification de la base de données"
echo ""
echo "📋 Dev:"
docker exec -it followup-php php bin/console doctrine:schema:validate
echo ""
echo "📋 Test:"
docker exec -it followup-php php bin/console doctrine:schema:validate --env=test
echo ""
echo "✅ Terminé"