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
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\Exception\ORMException;

class UserService
{
    private UserRepository $repository;
    private UserPasswordHasherInterface $hasher;
    private EntityManagerInterface $em;
    private EmailVerificationService $emailVerificationService;

    public function __construct(
        UserRepository $repository,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
        EmailVerificationService $emailVerificationService
    ) {
        $this->repository = $repository;
        $this->hasher = $hasher;
        $this->em = $em;
        $this->emailVerificationService = $emailVerificationService;
    }

    public function getAll(): array
    {
        return $this->repository->findAll();
    }

    public function getById(int $id): User
    {
        $user = $this->repository->find($id);

        if (!$user) {
            throw new NotFoundHttpException("Utilisateur #$id introuvable.");
        }

        return $user;
    }

    /**
     * ➕ Création d’un utilisateur + email de vérification
     */
    public function create(User $user): User
    {
        if ($this->repository->existsByEmail($user->getEmail())) {
            error_log('[FollowUp] Tentative de double inscription pour l\'email : ' . $user->getEmail());
            throw new ConflictHttpException("Cet email est déjà utilisé.");
        }

        if (!str_ends_with($user->getEmail(), '@gmail.com')) {
            throw new BadRequestHttpException(
                "Pour FollowUp, l'email doit être une adresse Gmail (ex : monjob.followup@gmail.com)."
            );
        }

        if ($user->getPassword() === null) {
            throw new BadRequestHttpException("Mot de passe requis.");
        }

        // Hash mot de passe
        $hashedPassword = $this->hasher->hashPassword($user, $user->getPassword());
        $user->setPassword($hashedPassword);

        $user->setIsVerified(false);

        try {
            // 1️⃣ Persist utilisateur (sans flush)
            $this->repository->save($user, false);

            // 2️⃣ Génération du token (uniquement s'il n'existe pas déjà)
            if (!$user->getEmailVerificationToken()) {
                $this->emailVerificationService->generateVerificationToken($user);
            }

            // 3️⃣ Flush UNIQUE
            $this->repository->save($user, true);
        } catch (UniqueConstraintViolationException $e) {
            // Cas de race condition : deux requêtes simultanées
            error_log('[FollowUp] Contrainte d\'unicité SQL violée pour l\'email : ' . $user->getEmail());
            throw new ConflictHttpException("Cet email est déjà utilisé.");
        } catch (DBALException | ORMException $e) {
            error_log('[FollowUp] Erreur transactionnelle lors de la création utilisateur : ' . $e->getMessage());
            throw new BadRequestHttpException(
                "Erreur lors de l’enregistrement du nouvel utilisateur."
            );
        }

        // 4️⃣ Envoi email (hors transaction DB)
        try {
            $this->emailVerificationService->sendVerificationEmail($user);
        } catch (\Throwable $e) {
            error_log('[FollowUp] Erreur lors de l\'envoi de l\'email de confirmation : ' . $e->getMessage());
            throw new BadRequestHttpException(
                "Le compte a été créé mais l'email de confirmation n'a pas pu être envoyé."
            );
        }

        return $user;
    }

    public function update(int $id, User $data): User
    {
        $user = $this->getById($id);

        if ($data->getEmail()) {
            if ($this->repository->existsByEmail($data->getEmail(), $id)) {
                throw new ConflictHttpException("Cet email est déjà utilisé.");
            }
            $user->setEmail($data->getEmail());
        }

        if ($data->getRoles()) {
            $user->setRoles($data->getRoles());
        }

        if ($data->getPassword()) {
            $hashedPassword = $this->hasher->hashPassword($user, $data->getPassword());
            $user->setPassword($hashedPassword);
        }

        try {
            $this->repository->save($user, true);
        } catch (DBALException | ORMException $e) {
            throw new BadRequestHttpException(
                "Erreur lors de la mise à jour de l’utilisateur."
            );
        }

        return $user;
    }

    public function delete(int $id): void
    {
        $user = $this->getById($id);

        try {
            $this->repository->remove($user, true);
        } catch (DBALException | ORMException $e) {
            throw new BadRequestHttpException(
                "Impossible de supprimer cet utilisateur pour le moment."
            );
        }
    }
}
