<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class OAuthUserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function getOrCreateFromGoogle(
        string $email,
        ?string $firstName,
        ?string $lastName,
        string $googleId
    ): User {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if ($user) {
            if ($user->isDeleted()) {
                throw new \RuntimeException('Ce compte a été supprimé.');
            }

            return $this->updateExistingUserWithOAuth($user, $googleId);
        }

        return $this->createOAuthUser($email, $firstName, $lastName, $googleId);
    }

    private function updateExistingUserWithOAuth(User $user, string $googleId): User
    {
        $needsUpdate = false;

        if ($user->getGoogleId() !== $googleId) {
            $user->setGoogleId($googleId);
            $needsUpdate = true;
        }

        if (!$user->isVerified()) {
            $user->setIsVerified(true);
            $user->setEmailVerificationToken(null);
            $user->setEmailVerificationTokenExpiresAt(null);
            $needsUpdate = true;
        }

        if ($needsUpdate) {
            $this->entityManager->flush();
        }

        return $user;
    }

    private function createOAuthUser(
        string $email,
        ?string $firstName,
        ?string $lastName,
        string $googleId
    ): User {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setGoogleId($googleId);
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(true);
        $user->setPassword(null);

        $user->setConsentRgpd(false);
        $user->setConsentRgpdAt(null);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}