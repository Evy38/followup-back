<?php

namespace App\Tests\Api;

use App\Tests\DatabasePrimer;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * 🎓 TEST D'INTÉGRATION : Inscription d'un utilisateur
 * 
 * Un test d'intégration teste le FLUX COMPLET de l'application :
 * Requête HTTP → Contrôleur → Service → BDD → Réponse JSON
 * 
 * 🎯 Objectif : Vérifier que l'endpoint POST /api/register fonctionne correctement
 * 📌 Critère REAC : "L'intégralité des tests exécutés sont conformes au plan de tests défini"
 * 
 * 💡 Ce qu'on teste :
 * - Le contrôleur reçoit bien la requête HTTP
 * - Le service UserService crée bien l'utilisateur
 * - L'utilisateur est bien enregistré en BDD
 * - La réponse HTTP est correcte (201 Created)
 * 
 * ❌ Ce qu'on ne teste PAS :
 * - L'envoi d'email (on utilise MAILER_DSN=null:// dans .env.test)
 */
class RegisterApiTest extends WebTestCase
{
    use DatabasePrimer; // Permet de réinitialiser la BDD avant chaque test

    /**
     * 🧪 TEST #1 : Inscription réussie avec des données valides
     * 
     * 🎯 Objectif : Vérifier qu'un utilisateur peut s'inscrire avec succès
     * 
     * 📌 Ce qu'on teste :
     * - La requête POST /api/register retourne 201 Created
     * - Le message de succès est correct
     * - L'utilisateur est bien enregistré en BDD
     */
    public function test_register_with_valid_data_should_create_user(): void
    {
        // ========================================
        // 1️⃣ ARRANGE : Préparer les données
        // ========================================
        
        // 🔹 Créer un client HTTP (comme Postman, mais dans les tests)
        $client = static::createClient();
        
        // 🔹 Préparer les données de la requête
        $data = [
            'email' => 'newuser@gmail.com',
            'password' => 'SecurePass123',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'consentRgpd' => true
        ];
        
        // ========================================
        // 2️⃣ ACT : Exécuter l'action (requête HTTP)
        // ========================================
        
        // 🔹 Faire une requête POST vers /api/register
        $client->request(
            'POST',                           // Méthode HTTP
            '/api/register',                  // URL
            [],                               // Paramètres GET (vide)
            [],                               // Fichiers (vide)
            ['CONTENT_TYPE' => 'application/json'], // Headers HTTP
            json_encode($data)                // Body JSON
        );
        
        // ========================================
        // 3️⃣ ASSERT : Vérifier le résultat
        // ========================================
        
        // ✅ Vérification 1 : Le statut HTTP doit être 201 Created
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        
        // ✅ Vérification 2 : La réponse doit être en JSON
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        
        // ✅ Vérification 3 : Le message de succès doit être présent
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertStringContainsString('Compte créé avec succès', $responseData['message']);
        
        // ✅ Vérification 4 : L'utilisateur doit être en BDD
        $userRepository = static::getContainer()->get('doctrine')->getRepository(\App\Entity\User::class);
        $user = $userRepository->findOneBy(['email' => 'newuser@gmail.com']);
        
        $this->assertNotNull($user, "L'utilisateur doit être enregistré en BDD");
        $this->assertEquals('newuser@gmail.com', $user->getEmail());
        $this->assertEquals('John', $user->getFirstName());
        $this->assertEquals('Doe', $user->getLastName());
        $this->assertFalse($user->getIsVerified(), "L'utilisateur ne doit pas être vérifié immédiatement");
        $this->assertTrue($user->getConsentRgpd(), "Le consentement RGPD doit être enregistré");
    }

    /**
     * 🧪 TEST #2 : Inscription échoue si l'email existe déjà
     * 
     * 🎯 Objectif : Vérifier qu'on ne peut pas créer 2 utilisateurs avec le même email
     * 
     * 📌 Ce qu'on teste :
     * - La première inscription réussit (201)
     * - La seconde inscription échoue (409 Conflict)
     * - Le message d'erreur est correct
     */
    public function test_register_with_existing_email_should_return_409_conflict(): void
    {
        // ========================================
        // 1️⃣ ARRANGE
        // ========================================
        
        $client = static::createClient();
        
        $data = [
            'email' => 'duplicate@gmail.com',
            'password' => 'SecurePass123'
        ];
        
        // 🔹 Première inscription : doit réussir
        $client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        
        // ========================================
        // 2️⃣ ACT : Tenter de s'inscrire AVEC LE MÊME EMAIL
        // ========================================
        
        $client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
        
        // ========================================
        // 3️⃣ ASSERT
        // ========================================
        
        // ✅ La seconde inscription doit échouer avec 409 Conflict
        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        
        // ✅ Le message d'erreur doit être correct
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertStringContainsString('déjà utilisé', $responseData['message']);
    }

    /**
     * 🧪 TEST #3 : Inscription échoue si l'email n'est pas Gmail
     * 
     * 🎯 Objectif : Vérifier la règle métier "l'email doit être Gmail"
     * 
     * 📌 Ce qu'on teste :
     * - Une inscription avec un email Yahoo doit échouer (400 Bad Request)
     */
    public function test_register_with_non_gmail_email_should_return_400_bad_request(): void
    {
        // ========================================
        // 1️⃣ ARRANGE
        // ========================================
        
        $client = static::createClient();
        
        $data = [
            'email' => 'test@yahoo.com', // ❌ Pas un Gmail
            'password' => 'SecurePass123'
        ];
        
        // ========================================
        // 2️⃣ ACT
        // ========================================
        
        $client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
        
        // ========================================
        // 3️⃣ ASSERT
        // ========================================
        
        // ✅ La réponse doit être 400 Bad Request
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        // ✅ Vérifier qu'une erreur est retournée (le message exact peut varier selon le catch)
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        // Note : Le contrôleur catch l'exception et retourne un message générique
    }

    /**
     * 🧪 TEST #4 : Inscription échoue si les données sont manquantes
     * 
     * 🎯 Objectif : Vérifier que l'API valide bien les données obligatoires
     * 
     * 📌 Ce qu'on teste :
     * - Une inscription sans email doit échouer (400 Bad Request)
     * - Une inscription sans mot de passe doit échouer (400 Bad Request)
     */
    public function test_register_with_missing_data_should_return_400_bad_request(): void
    {
        // ========================================
        // 1️⃣ ARRANGE
        // ========================================
        
        $client = static::createClient();
        
        // ========================================
        // 2️⃣ ACT + 3️⃣ ASSERT : Test sans email
        // ========================================
        
        $dataWithoutEmail = ['password' => 'SecurePass123'];
        $client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($dataWithoutEmail));
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        // ========================================
        // 2️⃣ ACT + 3️⃣ ASSERT : Test sans mot de passe
        // ========================================
        
        $dataWithoutPassword = ['email' => 'test@gmail.com'];
        $client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($dataWithoutPassword));
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}