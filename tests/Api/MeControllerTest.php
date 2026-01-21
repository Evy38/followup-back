<?php

namespace App\Tests\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class MeControllerTest extends WebTestCase
{
    /**
     * 🔒 Accès refusé sans JWT
     */
    public function testMeRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/me');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * 🚫 Accès refusé si compte non vérifié
     */
    public function testMeForbiddenIfUserNotVerified(): void
    {
        $client = static::createClient();

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
     * ✅ Accès autorisé si utilisateur vérifié
     */
    public function testMeReturnsUserEmail(): void
    {
        $client = static::createClient();

        $user = new User();
        $user->setEmail('verified@test.com');
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
        $this->assertEquals('verified@test.com', $responseData['email'] ?? null);
    }
}
