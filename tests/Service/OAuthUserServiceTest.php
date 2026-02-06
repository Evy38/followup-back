<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\OAuthUserService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class OAuthUserServiceTest extends TestCase
{
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private OAuthUserService $service;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->service = new OAuthUserService(
            $this->userRepository,
            $this->entityManager
        );
    }

    /**
     * ✅ Utilisateur existant déjà vérifié
     */
    public function testExistingVerifiedUserIsReturned(): void
    {
        $user = new User();
        $user->setEmail('test@gmail.com');
        $user->setGoogleId('google-id');
        $user->setIsVerified(true);

        $this->userRepository
            ->method('findOneBy')
            ->willReturn($user);

        $result = $this->service->getOrCreateFromGoogle(
            'test@gmail.com',
            'Test',
            'User',
            'google-id'
        );

        $this->assertSame($user, $result);
        $this->assertTrue($result->isVerified());
    }

    /**
     * 🔄 Utilisateur existant mais non vérifié → devient vérifié
     */
    public function testExistingUnverifiedUserIsVerified(): void
    {
        $user = new User();
        $user->setEmail('test@gmail.com');
        $user->setIsVerified(false);

        $this->userRepository
            ->method('findOneBy')
            ->willReturn($user);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->service->getOrCreateFromGoogle(
            'test@gmail.com',
            'Test',
            'User',
            'google-id'
        );

        $this->assertTrue($result->isVerified());
        $this->assertSame('google-id', $result->getGoogleId());
    }

    /**
     * 🆕 Nouvel utilisateur créé via Google
     */
    public function testNewUserIsCreatedAndVerified(): void
    {
        $this->userRepository
            ->method('findOneBy')
            ->willReturn(null);

        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $user = $this->service->getOrCreateFromGoogle(
            'new@gmail.com',
            'New',
            'User',
            'new-google-id'
        );

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('new@gmail.com', $user->getEmail());
        $this->assertTrue($user->isVerified());
        $this->assertSame('new-google-id', $user->getGoogleId());
        $this->assertNull($user->getPassword());
    }
}
