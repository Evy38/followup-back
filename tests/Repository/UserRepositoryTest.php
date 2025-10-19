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
        self::bootKernel(); // Démarre Symfony en mode test

        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->repository = $this->em->getRepository(User::class);
    }

    public function testSaveUser(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('Azerty123');
        $user->setRoles(['ROLE_USER']);

        // Persiste
        $this->repository->save($user, true);

        // Vérifie que l'ID a bien été généré (donc sauvegardé)
        $this->assertNotNull($user->getId());
    }

    public function testFindUserByEmail(): void
    {
        $user = $this->repository->findByEmail('test@example.com');
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('test@example.com', $user->getEmail());
    }

    public function testRemoveUser(): void
    {
        $user = $this->repository->findByEmail('test@example.com');
        $this->repository->remove($user, true);

        $deleted = $this->repository->findByEmail('test@example.com');
        $this->assertNull($deleted);
    }
}
