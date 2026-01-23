<?php

namespace App\Tests\Controller\Auth;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\User;

class RegisterControllerTest extends WebTestCase
{
    /**
     * 🔹 Test : échec si email ou mot de passe manquant
     */
    public function testRegisterFailsWhenDataIsMissing(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'test@example.com'
                // password manquant
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertStringContainsString(
            'Email et mot de passe requis.',
            $client->getResponse()->getContent()
        );
    }

    /**
     * 🔹 Test : succès inscription avec données valides
     */
    public function testRegisterSuccess(): void
    {
        $client = static::createClient();

        $email = 'newuser_' . uniqid() . '@gmail.com';

        $client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $email,
                'password' => 'StrongPassword123!',
                'firstName' => 'John',
                'lastName' => 'Doe'
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(
            'Compte créé. Veuillez confirmer votre adresse email pour activer votre compte.',
            $responseData['message'] ?? null
        );

        // 🔍 Vérification base de données
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $userRepository = $em->getRepository(User::class);

        $user = $userRepository->findOneBy(['email' => $email]);

        $this->assertNotNull($user);
        $this->assertFalse($user->isVerified());
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    /**
     * 🔹 Test : email invalide (validation Symfony)
     */
    public function testRegisterFailsWithInvalidEmail(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'not-an-email',
                'password' => 'password123'
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('email', $responseData);
    }
}
