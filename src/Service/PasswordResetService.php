<?php

namespace App\Service;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use DateTimeImmutable;

class PasswordResetService
{
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * Génère un token de réinitialisation et le stocke sur l'utilisateur
     * @throws NotFoundHttpException
     */
    public function requestReset(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            throw new NotFoundHttpException('Utilisateur non trouvé.');
        }

        $token = bin2hex(random_bytes(32));
        $expiration = (new DateTimeImmutable())->modify('+1 hour');

        $user->setPasswordResetToken($token);
        $user->setPasswordResetTokenExpiresAt($expiration);
        $this->entityManager->flush();
    }

    /**
     * Réinitialise le mot de passe de l'utilisateur à partir du token
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    public function resetPassword(string $token, string $newPassword): void
    {
        $user = $this->userRepository->findOneBy(['passwordResetToken' => $token]);
        if (!$user) {
            throw new NotFoundHttpException('Token invalide.');
        }

        $expiresAt = $user->getPasswordResetTokenExpiresAt();
        if (!$expiresAt || $expiresAt < new DateTimeImmutable()) {
            throw new BadRequestHttpException('Token expiré.');
        }

        if (!$this->isPasswordValid($newPassword)) {
            throw new BadRequestHttpException('Le mot de passe ne respecte pas la politique de sécurité.');
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        $user->setPasswordResetToken(null);
        $user->setPasswordResetTokenExpiresAt(null);
        $this->entityManager->flush();
    }

    /**
     * Politique de mot de passe : min 8 caractères, 1 majuscule, 1 chiffre
     */
    private function isPasswordValid(string $password): bool
    {
        return preg_match('/^(?=.*[A-Z])(?=.*\d).{8,}$/', $password) === 1;
    }
}
