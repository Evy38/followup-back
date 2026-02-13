<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class UserService
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly EntityManagerInterface $em,
        private readonly EmailVerificationService $emailVerificationService,
        private readonly SecurityEmailService $securityEmailService
    ) {
    }

    public function getAll(): array
    {
        return $this->repository->findBy(['deletedAt' => null]);
    }

    public function getById(int $id): User
    {
        $user = $this->repository->find($id);

        if (!$user || $user->isDeleted()) {
            throw new NotFoundHttpException("Utilisateur #$id introuvable.");
        }

        return $user;
    }

    public function create(User $user): User
    {
        if ($this->repository->existsByEmail($user->getEmail())) {
            throw new ConflictHttpException("Cet email est déjà utilisé.");
        }

        if (!str_ends_with($user->getEmail(), '@gmail.com')) {
            throw new BadRequestHttpException(
                "Pour FollowUp, l'email doit être une adresse Gmail."
            );
        }

        if ($user->getPassword() === null) {
            throw new BadRequestHttpException("Mot de passe requis.");
        }

        $hashedPassword = $this->hasher->hashPassword($user, $user->getPassword());
        $user->setPassword($hashedPassword);
        $user->setIsVerified(false);

        $this->emailVerificationService->generateVerificationToken($user);

        try {
            $this->repository->save($user, true);
        } catch (UniqueConstraintViolationException $e) {
            throw new ConflictHttpException("Cet email est déjà utilisé.");
        }

        try {
            $this->emailVerificationService->sendVerificationEmail($user);
        } catch (\Throwable $e) {
            throw new BadRequestHttpException(
                "Le compte a été créé mais l'email de confirmation n'a pas pu être envoyé."
            );
        }

        return $user;
    }

    public function update(int $id, User $data, ?string $currentPassword = null): User
    {
        $user = $this->getById($id);

        if ($user->isDeleted()) {
            throw new BadRequestHttpException("Ce compte est supprimé.");
        }

        if ($data->getEmail() && $data->getEmail() !== $user->getEmail()) {
            if ($this->repository->existsByEmail($data->getEmail(), $id)) {
                throw new ConflictHttpException("Cet email est déjà utilisé.");
            }

            $user->setPendingEmail($data->getEmail());
            $this->emailVerificationService->generateVerificationToken($user);
            $this->repository->save($user, true);
            $this->emailVerificationService->sendVerificationEmail($user);

            return $user;
        }

        if ($data->getFirstName() !== null) {
            $user->setFirstName($data->getFirstName());
        }

        if ($data->getLastName() !== null) {
            $user->setLastName($data->getLastName());
        }

        if ($data->getPassword()) {
            if ($user->isOauthUser()) {
                throw new BadRequestHttpException(
                    "Impossible de modifier le mot de passe pour un compte OAuth."
                );
            }

            if ($currentPassword === null) {
                throw new BadRequestHttpException("Ancien mot de passe requis.");
            }

            if (!$this->hasher->isPasswordValid($user, $currentPassword)) {
                throw new BadRequestHttpException("Ancien mot de passe incorrect.");
            }

            $hashedPassword = $this->hasher->hashPassword($user, $data->getPassword());
            $user->setPassword($hashedPassword);

            $this->repository->save($user, true);

            try {
                $this->securityEmailService->sendPasswordChangedEmail($user);
            } catch (\Throwable $e) {
            }

            return $user;
        }

        $this->repository->save($user, true);

        return $user;
    }

    public function requestDeletion(User $user): void
    {
        if ($user->isDeleted()) {
            throw new BadRequestHttpException("Ce compte est déjà supprimé.");
        }

        $user->requestDeletion();
        $user->softDelete();

        $this->repository->save($user, true);

        try {
            $this->securityEmailService->sendAccountDeletionRequestEmail($user);
        } catch (\Throwable $e) {
            // Log mais ne bloque pas
        }
    }

    public function hardDelete(int $id): void
    {
        $user = $this->repository->find($id);

        if (!$user) {
            throw new NotFoundHttpException("Utilisateur #$id introuvable.");
        }

        if (!$user->isDeleted()) {
            throw new BadRequestHttpException("Le compte doit être soft deleted avant suppression définitive.");
        }

        $email = $user->getEmail();
        $firstName = $user->getFirstName() ?? 'Utilisateur';

        $this->em->remove($user);
        $this->em->flush();

        try {
            $this->securityEmailService->sendAccountDeletionConfirmationEmail($email, $firstName);
        } catch (\Throwable $e) {
            // Log mais ne bloque pas
        }
    }

    public function save(User $user): void
    {
        $this->repository->save($user, true);
    }
}