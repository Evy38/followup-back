<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\Exception\ORMException;

class UserService
{
    // 💡 Dépendances nécessaires au fonctionnement du service
    private UserRepository $repository;              // pour accéder à la BDD via Doctrine
    private UserPasswordHasherInterface $hasher;     // pour chiffrer les mots de passe
    private EntityManagerInterface $em;              // pour persister, supprimer, flusher

    // 💡 Symfony injecte automatiquement ces dépendances au moment où le service est créé
    public function __construct(
        UserRepository $repository,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em
    ) {
        $this->repository = $repository;
        $this->hasher = $hasher;
        $this->em = $em;
    }

    /**
     * 📋 Récupère tous les utilisateurs
     */
    public function getAll(): array
    {
        return $this->repository->findAll();
    }

    /**
     * 🔍 Récupère un utilisateur par ID (ou erreur 404 s’il n’existe pas)
     */
    public function getById(int $id): User
    {
        $user = $this->repository->find($id);

        if (!$user) {
            throw new NotFoundHttpException("Utilisateur #$id introuvable.");
        }

        return $user;
    }

    /**
     * ➕ Crée un nouvel utilisateur
     */
    public function create(User $user): User
    {
        // Vérifie si l’email existe déjà
        if ($this->repository->existsByEmail($user->getEmail())) {
            throw new ConflictHttpException("Cet email est déjà utilisé.");
        }

        // Règle métier : email Gmail obligatoire
        if (!str_ends_with($user->getEmail(), '@gmail.com')) {
            throw new BadRequestHttpException("Pour FollowUp, l'email doit être une adresse Gmail (ex : monjob.followup@gmail.com).");
        }

        // Hash du mot de passe (jamais stocké en clair)
        $hashed = $this->hasher->hashPassword($user, $user->getPassword());
        $user->setPassword($hashed);

        // Essaye d’enregistrer le user dans la base
        try {
            $this->repository->save($user, true); // true = flush immédiat
        } catch (DBALException|ORMException $e) {
            // Si Doctrine échoue, on renvoie une erreur claire
            throw new BadRequestHttpException("Erreur lors de l’enregistrement du nouvel utilisateur.");
        }

        return $user;
    }

    /**
     * ♻️ Met à jour un utilisateur existant
     */
    public function update(int $id, User $data): User
    {
        $user = $this->getById($id); // on récupère l’utilisateur existant

        // Vérifie s’il y a un nouvel email et s’il est déjà pris
        if ($data->getEmail()) {
            if ($this->repository->existsByEmail($data->getEmail(), $id)) {
                throw new ConflictHttpException("Cet email est déjà utilisé.");
            }
            $user->setEmail($data->getEmail());
        }

        // Met à jour les rôles si fournis
        if ($data->getRoles()) {
            $user->setRoles($data->getRoles());
        }

        // Met à jour le mot de passe si fourni
        if ($data->getPassword()) {
            $hashed = $this->hasher->hashPassword($user, $data->getPassword());
            $user->setPassword($hashed);
        }

        // On essaye d’enregistrer les changements
        try {
            $this->repository->save($user, true);
        } catch (DBALException|ORMException $e) {
            throw new BadRequestHttpException("Erreur lors de la mise à jour de l’utilisateur.");
        }

        return $user;
    }

    /**
     * ❌ Supprime un utilisateur
     */
    public function delete(int $id): void
    {
        $user = $this->getById($id); // 404 si introuvable

        try {
            $this->repository->remove($user, true);
        } catch (DBALException|ORMException $e) {
            throw new BadRequestHttpException("Impossible de supprimer cet utilisateur pour le moment.");
        }
    }
}
