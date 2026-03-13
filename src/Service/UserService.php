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

/**
 * Service métier pour la gestion des utilisateurs.
 *
 * Responsabilités :
 * - Création de compte (hashage mot de passe, envoi email de vérification)
 * - Mise à jour du profil (email, prénom, nom, mot de passe)
 * - Gestion du cycle de vie du compte (demande de suppression, suppression définitive, purge)
 *
 * Règles métier notables :
 * - L'email doit être une adresse Gmail (`@gmail.com`) à la création
 * - Le changement d'email passe par une étape de confirmation (pendingEmail + token)
 * - La suppression suit un workflow en 2 étapes : demande utilisateur → confirmation admin
 * - La purge supprime définitivement les comptes marqués comme supprimés depuis plus d'1 mois
 * - Les comptes OAuth ne peuvent pas modifier leur mot de passe
 */
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

    /**
     * Retourne tous les utilisateurs actifs (non supprimés).
     *
     * @return User[]
     */
    public function getAll(): array
    {
        return $this->repository->findBy(['deletedAt' => null]);
    }

    /**
     * Récupère un utilisateur actif par son identifiant.
     *
     * @throws NotFoundHttpException Si l'utilisateur n'existe pas ou est supprimé
     */
    public function getById(int $id): User
    {
        $user = $this->repository->find($id);

        if (!$user || $user->isDeleted()) {
            throw new NotFoundHttpException("Utilisateur #$id introuvable.");
        }

        return $user;
    }

    /**
     * Crée un nouveau compte utilisateur avec vérification d'email.
     *
     * Étapes :
     * 1. Vérifie l'unicité de l'email et que c'est une adresse Gmail
     * 2. Hache le mot de passe en clair fourni
     * 3. Génère un token de vérification (valable 24h) et persiste l'utilisateur
     * 4. Envoie l'email de confirmation
     *
     * @throws ConflictHttpException   Si l'email est déjà utilisé
     * @throws BadRequestHttpException Si l'email n'est pas Gmail ou le mot de passe manquant
     */
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

    /**
     * Met à jour le profil d'un utilisateur actif.
     *
     * Cas gérés :
     * - Changement d'email : stocké en `pendingEmail`, un email de confirmation est envoyé
     * - Changement de prénom/nom : mise à jour directe
     * - Changement de mot de passe : nécessite l'ancien mot de passe ; impossible pour les comptes OAuth
     *
     * @param int         $id              Identifiant de l'utilisateur à modifier
     * @param User        $data            Objet User portant les nouvelles valeurs
     * @param string|null $currentPassword Ancien mot de passe en clair (requis pour changer le mot de passe)
     *
     * @throws ConflictHttpException   Si le nouvel email est déjà utilisé
     * @throws BadRequestHttpException Si l'ancien mot de passe est absent ou incorrect, ou si compte OAuth
     */
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

        $nameChanged = $data->getFirstName() !== null || $data->getLastName() !== null;

        $this->repository->save($user, true);

        if ($nameChanged) {
            try {
                $this->securityEmailService->sendProfileNameChangedEmail($user);
            } catch (\Throwable $e) {
            }
        }

        return $user;
    }

    /**
     * Enregistre la demande de suppression de compte de l'utilisateur.
     *
     * Marque le compte via `deletionRequestedAt`. Le compte reste accessible jusqu'à
     * la validation par un administrateur (`hardDelete`). Un email de confirmation est
     * envoyé en différé.
     *
     * @throws BadRequestHttpException Si le compte est déjà supprimé
     */
    public function requestDeletion(User $user): void
    {
        if ($user->isDeleted()) {
            throw new BadRequestHttpException("Ce compte est déjà supprimé.");
        }

        $user->requestDeletion();

        $this->repository->save($user, true);

        try {
            $this->securityEmailService->sendAccountDeletionRequestEmail($user);
        } catch (\Throwable $e) {
            // Log mais ne bloque pas
        }
    }

    /**
     * Confirme la suppression d'un compte (action réservée à l'administrateur).
     *
     * Pose `deletedAt` sur le compte. Requiert que l'utilisateur ait préalablement
     * fait une demande de suppression (`deletionRequestedAt` non null).
     * Un email de confirmation est envoyé en différé.
     *
     * @throws NotFoundHttpException   Si l'utilisateur est introuvable
     * @throws BadRequestHttpException Si aucune demande de suppression n'a été faite
     */
    public function hardDelete(int $id): void
    {
        $user = $this->repository->find($id);

        if (!$user) {
            throw new NotFoundHttpException("Utilisateur #$id introuvable.");
        }

        // L'utilisateur doit avoir demandé la suppression
        if (!$user->getDeletionRequestedAt()) {
            throw new BadRequestHttpException(
                "Le compte doit demander une suppression avant confirmation."
            );
        }

        // Marquer comme supprimé (confirmation admin)
        $user->setDeletedAt(new \DateTimeImmutable());
        $this->repository->save($user, true);

        $email = $user->getEmail();
        $firstName = $user->getFirstName() ?? 'Utilisateur';

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

    /**
     * Supprime définitivement en base les comptes marqués comme supprimés depuis plus d'1 mois.
     *
     * Utilisé par la commande de purge administrative.
     * Toutes les candidatures liées sont supprimées en cascade (onDelete: CASCADE sur User).
     *
     * @return int Nombre de comptes purgés
     */
    public function purgeOldDeletedUsers(): int
    {
        $users = $this->repository->findPurgeable();

        foreach ($users as $user) {
            $this->repository->remove($user, false);
        }

        if (count($users) > 0) {
            $this->repository->flush();
        }

        return count($users);
    }
}