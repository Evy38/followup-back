<?php

namespace App\Tests\Controller;

use App\Controller\Auth\ForgotPasswordController;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\DatabaseTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ForgotPasswordControllerTest extends DatabaseTestCase
{
    private UserPasswordHasherInterface $passwordHasher;
    private UserRepository $userRepository;
    private ForgotPasswordController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $container = static::getContainer();
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->controller = new ForgotPasswordController();
    }

    public function testForgotPasswordUpdatesHashedPassword(): void
    {
        $user = new User();
        $user->setEmail('reset.test@gmail.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'OldPass1'));
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $payload = json_encode([
            'email' => 'reset.test@gmail.com',
            'newPassword' => 'NewPass1',
            'confirmPassword' => 'NewPass1',
        ]);

        $request = new Request([], [], [], [], [], [], $payload);

        $response = $this->controller->forgotPassword(
            $request,
            $this->userRepository,
            $this->passwordHasher,
            $this->entityManager
        );

        $this->assertSame(200, $response->getStatusCode());

        $this->entityManager->clear();
        $updatedUser = $this->userRepository->findOneBy(['email' => 'reset.test@gmail.com']);
        $this->assertTrue($this->passwordHasher->isPasswordValid($updatedUser, 'NewPass1'));
    }
}
