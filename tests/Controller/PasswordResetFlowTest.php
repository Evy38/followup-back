<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordResetFlowTest extends WebTestCase
{
    public function testPasswordResetFlowUpdatesPassword(): void
    {
        // 1) Créer un client Symfony
        $client = static::createClient();
        $container = $client->getContainer();

        // 2) Récupérer les services nécessaires
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        /** @var UserRepository $userRepository */
        $userRepository = $container->get(UserRepository::class);
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);

        // 3) Nettoyer la table user avant le test
        $connection = $em->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $connection->executeStatement('DELETE FROM user');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');

        // 4) Créer un utilisateur avec email et mot de passe hashé
        $user = new User();
        $user->setEmail('reset.test@gmail.com');
        $user->setPassword($hasher->hashPassword($user, 'OldPass1'));
        $user->setRoles(['ROLE_USER']);
        $em->persist($user);
        $em->flush();

        // 5) Appeler POST /api/password/request avec le bon JSON
        $client->request('POST', '/api/password/request', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'email' => 'reset.test@gmail.com'
        ]));

        // 6) Vérifier HTTP 200
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        // 7) Recharger l'utilisateur depuis la base
        $em->clear();
        $user = $userRepository->findOneBy(['email' => 'reset.test@gmail.com']);

        // 8) Récupérer resetPasswordToken
        $token = $user->getResetPasswordToken();
        $this->assertNotNull($token, 'Le token de reset doit être généré');

        // 9) Appeler POST /api/password/reset avec le token et les nouveaux mots de passe
        $client->request('POST', '/api/password/reset', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'token' => $token,
            'newPassword' => 'NewPass1',
            'confirmPassword' => 'NewPass1',
        ]));

        // 10) Vérifier HTTP 200
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        // 11) Recharger l'utilisateur
        $em->clear();
        $user = $userRepository->findOneBy(['email' => 'reset.test@gmail.com']);

        // 12) Vérifier que le mot de passe est bien modifié
        $this->assertTrue(
            $hasher->isPasswordValid($user, 'NewPass1'),
            'Le mot de passe doit être modifié et hashé.'
        );
    }
}
