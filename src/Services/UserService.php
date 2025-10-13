<?php
#Centralise la logique metier, validations, transactions
namespace App\Services;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class UserService
{
    private UserRepository $repository;
    private UserPasswordHasherInterface $passwordHasher; #fourni par symfony pour créer des hashs sécurisés
    private EntityManagerInterface $em; # pour exécuter persist(), flush(), remove()

    public function __construct(
        UserRepository $repository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ) {
        $this->repository = $repository;
        $this->passwordHasher = $passwordHasher;
        $this->em = $em;
    }

    /**
     * Récupère tous les utilisateurs
     */
    public function getAll(): array
    {
        return $this->repository->findAll();
    }

    /**
     * Récupère un utilisateur par son ID
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
     * Crée un nouvel utilisateur avec vérification d’unicité et hash du mot de passe
     */
    public function create(User $user): User
    {
        // Vérif : email déjà existant ?
        $existing = $this->repository->findOneBy(['email' => $user->getEmail()]);
        if ($existing) {
            throw new ConflictHttpException("Cet email est déjà utilisé.");
        }

        // Hash du mot de passe
        $hashed = $this->passwordHasher->hashPassword($user, $user->getPassword());
        $user->setPassword($hashed);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

/**
     * Met à jour un utilisateur existant
     */
    public function update(int $id, User $data): User
    {
        $user = $this->getById($id);

        if ($data->getEmail()) {
            $existing = $this->repository->findOneBy(['email' => $data->getEmail()]);
            if ($existing && $existing->getId() !== $id) {
                throw new ConflictHttpException("Cet email est déjà utilisé.");
            }
            $user->setEmail($data->getEmail());
        }

        if ($data->getRoles()) {
            $user->setRoles($data->getRoles());
        }

        if ($data->getPassword()) {
            $hashed = $this->passwordHasher->hashPassword($user, $data->getPassword());
            $user->setPassword($hashed);
        }

        $this->em->flush();
        return $user;
    }

 /**
     * Supprime un utilisateur existant
     */
    public function delete(int $id): void
    {
        $user = $this->getById($id);
        $this->em->remove($user);
        $this->em->flush();
    }
}
