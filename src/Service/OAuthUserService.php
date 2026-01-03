<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class OAuthUserService
{
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(UserRepository $userRepository, EntityManagerInterface $entityManager)
    {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Récupère un utilisateur existant par email ou le crée à partir des infos Google.
     *
     * @param string $email Email Google
     * @param string|null $firstName Prénom Google
     * @param string|null $lastName Nom Google
     * @param string $googleId Identifiant Google
     * @return User
     */
    public function getOrCreateFromGoogle(string $email, ?string $firstName, ?string $lastName, string $googleId): User
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if ($user) {
            // Met à jour l'identifiant Google si besoin
            if ($user->getGoogleId() !== $googleId) {
                $user->setGoogleId($googleId);
                $this->entityManager->flush();
            }
            return $user;
        }

        // Création d'un nouvel utilisateur sans mot de passe
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setGoogleId($googleId);
        $user->setRoles(['ROLE_USER']);
        $user->setPassword(null); // Pas de mot de passe pour OAuth

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
