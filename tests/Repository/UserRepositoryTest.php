<?php

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

class UserRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private UserRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->repository = static::getContainer()->get(UserRepository::class);
        // Nettoyage complet de la table user avant chaque test (FK safe)
        $connection = $this->em->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $connection->executeStatement('DELETE FROM user');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function testSaveUser(): void
    {
        $user = new User();
        $user->setEmail('saveuser@example.com');
        $user->setPassword('Azerty123');
        $user->setRoles(['ROLE_USER']);
        $this->repository->save($user, true);
        $this->assertNotNull($user->getId());
    }

    public function testFindUserByEmail(): void
    {
        $user = new User();
        $user->setEmail('finduser@example.com');
        $user->setPassword('Azerty123');
        $user->setRoles(['ROLE_USER']);
        $this->repository->save($user, true);

        $found = $this->repository->findByEmail('finduser@example.com');
        $this->assertInstanceOf(User::class, $found);
        $this->assertSame('finduser@example.com', $found->getEmail());
    }

    public function testRemoveUser(): void
    {
        $user = new User();
        $user->setEmail('removeuser@example.com');
        $user->setPassword('Azerty123');
        $user->setRoles(['ROLE_USER']);
        $this->repository->save($user, true);

        $this->repository->remove($user, true);
        $deleted = $this->repository->findByEmail('removeuser@example.com');
        $this->assertNull($deleted);
    }
}
