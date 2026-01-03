<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserServiceTest extends KernelTestCase
{
    private UserService $service;
    private UserRepository $repository;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->repository = $container->get(UserRepository::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $this->service = new UserService($this->repository, $hasher, $this->em);

        // 🧹 Nettoyage complet de la table user avant chaque test
        // On désactive temporairement les contraintes FK pour éviter les erreurs si d'autres tables référencent user
        $connection = $this->em->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $connection->executeStatement('DELETE FROM user');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        // On peut aussi utiliser truncate si besoin, mais DELETE est plus sûr pour la compatibilité FK
    }

    public function testCreateUser(): void
    {
        $user = new User();
        $user->setEmail('testservice@gmail.com');
        $user->setPassword('Azerty123');
        $user->setRoles(['ROLE_USER']);

        $created = $this->service->create($user);

        $this->assertNotNull($created->getId());
        $this->assertNotEquals('Azerty123', $created->getPassword(), "Le mot de passe doit être hashé");
    }

    public function testCreateUserWithDuplicateEmailThrowsException(): void
    {
        $this->expectException(ConflictHttpException::class);

        $user1 = new User();
        $user1->setEmail('dup@gmail.com');
        $user1->setPassword('Azerty123');
        $this->service->create($user1);

        $user2 = new User();
        $user2->setEmail('dup@gmail.com');
        $user2->setPassword('Azerty123');
        $this->service->create($user2); // doit lever une exception
    }

    public function testUpdateUser(): void
    {
        $user = new User();
        $user->setEmail('update@gmail.com');
        $user->setPassword('Azerty123');
        $user->setRoles(['ROLE_USER']);
        $this->service->create($user);

        $updatedData = new User();
        $updatedData->setEmail('updated@gmail.com');
        $updatedData->setPassword('NewPass123');

        $updated = $this->service->update($user->getId(), $updatedData);

        $this->assertSame('updated@gmail.com', $updated->getEmail());
        $this->assertNotEquals('NewPass123', $updated->getPassword(), "Le mot de passe doit être hashé");
    }

    public function testDeleteUser(): void
    {
        $user = new User();
        $user->setEmail('delete@gmail.com');
        $user->setPassword('Azerty123');
        $this->service->create($user);

        $id = $user->getId();
        $this->service->delete($id);

        $this->expectException(NotFoundHttpException::class);
        $this->service->getById($id);
    }

        public function testPasswordIsHashedUsingSymfonyHasher(): void
    {
        $user = new User();
        $user->setEmail('secure@gmail.com');
        $user->setPassword('Azerty123');

        $created = $this->service->create($user);

        // 🔒 Le mot de passe ne doit jamais être stocké en clair
        $this->assertNotSame('Azerty123', $created->getPassword(), 'Le mot de passe ne doit pas être stocké en clair.');

        // ✅ Il doit être encodé avec bcrypt ou argon2i (selon config)
        $this->assertMatchesRegularExpression(
            '/^\$2[ayb]\$.{56}$/', 
            $created->getPassword(),
            'Le hash doit correspondre au format bcrypt.'
        );
    }

}
