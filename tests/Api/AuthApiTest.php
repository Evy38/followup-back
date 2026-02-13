<?php

namespace App\Tests\Api;

use App\Entity\User;
use App\Tests\DatabasePrimer;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * 🎓 TEST D'INTÉGRATION : Authentification JWT
 * 
 * Version simplifiée : Utilise des emails uniques pour éviter les conflits
 */
class AuthApiTest extends WebTestCase
{
    use DatabasePrimer;

    /**
     * 🧪 TEST #1 : Connexion réussie avec des identifiants valides
     */
    public function test_login_with_valid_credentials_should_return_jwt_token(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        
        // Utiliser un email unique pour ce test
        $user = new User();
        $user->setEmail('test1_' . uniqid() . '@gmail.com');
        $user->setPassword($hasher->hashPassword($user, 'Password123'));
        $user->setIsVerified(true);
        $user->setRoles(['ROLE_USER']);
        
        $entityManager->persist($user);
        $entityManager->flush();
        
        $loginData = [
            'email' => $user->getEmail(),
            'password' => 'Password123'
        ];
        
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($loginData));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $responseData);
        $this->assertIsString($responseData['token']);
        $this->assertNotEmpty($responseData['token']);
        
        $tokenParts = explode('.', $responseData['token']);
        $this->assertCount(3, $tokenParts);
    }

    /**
     * 🧪 TEST #2 : Connexion échoue avec un mot de passe incorrect
     */
    public function test_login_with_invalid_password_should_return_401_unauthorized(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        
        $user = new User();
        $user->setEmail('test2_' . uniqid() . '@gmail.com');
        $user->setPassword($hasher->hashPassword($user, 'CorrectPassword123'));
        $user->setIsVerified(true);
        $user->setRoles(['ROLE_USER']);
        
        $entityManager->persist($user);
        $entityManager->flush();
        
        $loginData = [
            'email' => $user->getEmail(),
            'password' => 'WrongPassword456'
        ];
        
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($loginData));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * 🧪 TEST #3 : Connexion échoue si l'utilisateur n'existe pas
     */
    public function test_login_with_non_existent_user_should_return_401_unauthorized(): void
    {
        $client = static::createClient();
        
        $loginData = [
            'email' => 'nonexistent_' . uniqid() . '@gmail.com',
            'password' => 'Password123'
        ];
        
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($loginData));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * 🧪 TEST #4 : Connexion échoue si les données sont manquantes
     */
    public function test_login_with_missing_credentials_should_return_400_bad_request(): void
    {
        $client = static::createClient();
        
        // Test sans email
        $dataWithoutEmail = ['password' => 'Password123'];
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($dataWithoutEmail));
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        // Test sans password
        $dataWithoutPassword = ['email' => 'test@gmail.com'];
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($dataWithoutPassword));
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}