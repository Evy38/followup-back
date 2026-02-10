<?php

namespace App\Tests\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class MeControllerTest extends WebTestCase
{
    /**
     * Nettoie la base de données avant chaque test.
     */
    private function cleanDatabase(): void
    {
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        $connection = $em->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $connection->executeStatement('DELETE FROM user');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Test de sécurité : Accès refusé sans JWT.
     */
    public function testMeRequiresAuthentication(): void
    {
        $client = static::createClient();
        $this->cleanDatabase(); // Appelé APRÈS createClient()

        $client->request('GET', '/api/me');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Test de sécurité : Accès refusé si compte non vérifié.
     */
    public function testMeForbiddenIfUserNotVerified(): void
    {
        $client = static::createClient();
        $this->cleanDatabase();

        $user = new User();
        $user->setEmail('notverified_' . uniqid() . '@test.com');
        $user->setIsVerified(false);
        $user->setRoles(['ROLE_USER']);

        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $em->persist($user);
        $em->flush();
        
        $client->loginUser($user);
        $client->request('GET', '/api/me');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * Test fonctionnel : Accès autorisé avec utilisateur vérifié.
     */
    public function testMeReturnsUserEmail(): void
    {
        $client = static::createClient();
        $this->cleanDatabase();

        $user = new User();
        $user->setEmail('verified_' . uniqid() . '@test.com'); // Email unique
        $user->setIsVerified(true);
        $user->setRoles(['ROLE_USER']);

        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $em->persist($user);
        $em->flush();
        
        $client->loginUser($user);
        $client->request('GET', '/api/me');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($user->getEmail(), $responseData['user']['email'] ?? null);
    }
}