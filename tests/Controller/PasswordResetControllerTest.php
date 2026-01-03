<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordResetControllerTest extends WebTestCase
{
    private EntityManagerInterface $em;
    private UserRepository $repository;
    private UserPasswordHasherInterface $hasher;
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = $this->client->getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repository = $container->get(UserRepository::class);
        $this->hasher = $container->get(UserPasswordHasherInterface::class);
        // Nettoyage complet de la table user avant chaque test (FK safe)
        $connection = $this->em->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $connection->executeStatement('DELETE FROM user');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function testPasswordResetFlowUpdatesPassword(): void
    {
        $client = $this->client;

        // Création d'un utilisateur avec mot de passe hashé
        $user = new User();
        $user->setEmail('resetflow@example.com');
        $user->setPassword($this->hasher->hashPassword($user, 'OldPassword123'));
        $user->setRoles(['ROLE_USER']);
        $this->repository->save($user, true);

        // 1. Demande de reset
        $client->request('POST', '/api/password/request', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'resetflow@example.com'
        ]));
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertStringContainsString('Si un compte existe', $client->getResponse()->getContent());

        // 2. Récupérer le token
        $user = $this->repository->findOneBy(['email' => 'resetflow@example.com']);
        $token = $user->getResetPasswordToken();
        $this->assertNotNull($token, 'Le token de reset doit être généré');

        // 3. Reset du mot de passe
        $client->request('POST', '/api/password/reset', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'token' => $token,
            'newPassword' => 'NewPassword123',
            'confirmPassword' => 'NewPassword123'
        ]));
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        // 4. Vérifier que le mot de passe a bien changé
        $user = $this->repository->findOneBy(['email' => 'resetflow@example.com']);
        $this->assertTrue(
            $this->hasher->isPasswordValid($user, 'NewPassword123'),
            'Le mot de passe doit être modifié et hashé.'
        );
    }
}
