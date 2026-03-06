<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Gère la création et la mise à jour des comptes utilisateur via OAuth Google.
 *
 * Responsabilités :
 * - Retrouver un utilisateur existant à partir de son email Google
 * - Créer un nouveau compte si l'utilisateur n'a jamais utilisé FollowUp
 * - Lier le `googleId` au compte existant si la connexion OAuth est nouvelle
 * - Marquer automatiquement le compte comme vérifié (l'OAuth prouve la possession de l'email)
 *
 * Les nouveaux comptes OAuth n'ont pas de mot de passe (`password = null`).
 * Le consentement RGPD est demandé séparément après la première connexion OAuth.
 *
 * @see \App\Controller\Auth\AuthController::googleCallback()
 */
class OAuthUserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager
    ) {}

    /**
     * Récupère ou crée un utilisateur à partir des informations Google OAuth.
     *
     * Si un compte avec cet email existe déjà, il est mis à jour avec le googleId.
     * Si le compte a été supprimé (soft delete), une RuntimeException est levée.
     * Sinon, un nouveau compte est créé avec isVerified = true et password = null.
     *
     * @param string      $email     Adresse email fournie par Google
     * @param string|null $firstName Prénom (peut être null selon la configuration du compte Google)
     * @param string|null $lastName  Nom de famille
     * @param string      $googleId  Identifiant unique Google (subject ID)
     *
     * @throws \RuntimeException Si le compte associé à l'email est soft-deleted
     */
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

        if (!$user->getIsVerified()) {
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