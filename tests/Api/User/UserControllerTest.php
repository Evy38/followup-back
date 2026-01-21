<?php

namespace App\Tests\Api\User;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends WebTestCase
{
    /**
     * 🔒 Accès refusé sans authentification
     */
    public function testProfileRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/user/profile');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * ✅ Récupération du profil utilisateur connecté
     */
    public function testGetProfileReturnsUserData(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $user = $this->createUser();
        $em->persist($user);
        $em->flush();
        $client->loginUser($user);

        $client->request('GET', '/api/user/profile');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('user@test.com', $responseData['email'] ?? null);
    }

    /**
     * ✏️ Mise à jour du profil utilisateur
     */
    public function testUpdateProfile(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $user = new \App\Entity\User();
        $user->setEmail('user_' . uniqid() . '@test.com');
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(true);
        $em->persist($user);
        $em->flush();
        $client->loginUser($user);

        $client->request(
            'PUT',
            '/api/user/profile',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'firstName' => 'Updated',
                'lastName' => 'User'
            ])
        );

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Updated', $responseData['firstName'] ?? null);
        $this->assertEquals('User', $responseData['lastName'] ?? null);
    }

    /**
     * 🚫 Liste des utilisateurs interdite sans ROLE_ADMIN
     */
    public function testListUsersForbiddenForNonAdmin(): void
    {
        $client = static::createClient();
        $client->loginUser($this->createUser());

        $client->request('GET', '/api/user');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * ✅ Liste des utilisateurs accessible pour ROLE_ADMIN
     */
    public function testListUsersAllowedForAdmin(): void
    {
        $client = static::createClient();
        $client->loginUser($this->createAdmin());

        $client->request('GET', '/api/user');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
    }

    // =====================
    // Helpers
    // =====================

    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('user@test.com');
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(true);

        return $user;
    }

    private function createAdmin(): User
    {
        $admin = new User();
        $admin->setEmail('admin@test.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setIsVerified(true);

        return $admin;
    }
}
