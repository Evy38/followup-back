<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de gestion des utilisateurs OAuth (Google).
 * 
 * Responsabilités :
 * - Récupérer un utilisateur existant par email
 * - Créer un nouvel utilisateur depuis les données OAuth
 * - Vérifier automatiquement l'email (prouvé par OAuth)
 * - Associer le googleId à l'utilisateur
 * 
 * Règle métier : Les utilisateurs OAuth n'ont pas de mot de passe local.
 */
class OAuthUserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Récupère un utilisateur existant ou le crée depuis les données Google.
     * 
     * Workflow :
     * 1. Recherche par email
     * 2. Si trouvé : met à jour googleId + vérifie l'email automatiquement
     * 3. Si non trouvé : crée un nouvel utilisateur vérifié
     * 
     * @param string $email Email vérifié par Google
     * @param string|null $firstName Prénom Google
     * @param string|null $lastName Nom Google
     * @param string $googleId Identifiant unique Google
     * 
     * @return User L'utilisateur existant ou nouvellement créé
     */
    public function getOrCreateFromGoogle(
        string $email,
        ?string $firstName,
        ?string $lastName,
        string $googleId
    ): User {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        // Cas 1 : Utilisateur existant
        if ($user) {
            return $this->updateExistingUserWithOAuth($user, $googleId);
        }

        // Cas 2 : Nouvel utilisateur OAuth
        return $this->createOAuthUser($email, $firstName, $lastName, $googleId);
    }

    /**
     * Met à jour un utilisateur existant avec les données OAuth.
     * 
     * Actions :
     * - Associe le googleId si absent
     * - Vérifie l'email automatiquement (OAuth prouve la possession)
     * - Supprime les tokens de vérification obsolètes
     */
    private function updateExistingUserWithOAuth(User $user, string $googleId): User
    {
        $needsUpdate = false;

        // Association du googleId si manquant
        if ($user->getGoogleId() !== $googleId) {
            $user->setGoogleId($googleId);
            $needsUpdate = true;
        }

        // Vérification automatique de l'email (OAuth prouve la possession)
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

    /**
     * Crée un nouvel utilisateur depuis les données OAuth.
     * 
     * Caractéristiques :
     * - Aucun mot de passe (authentification via OAuth uniquement)
     * - Email automatiquement vérifié
     * - Rôle USER par défaut
     */
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