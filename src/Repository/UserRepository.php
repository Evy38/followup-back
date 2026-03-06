<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * Repository pour l'entité User.
 *
 * Fournit les méthodes d'accès aux données relatives aux utilisateurs,
 * notamment pour la gestion du cycle de vie (soft delete, purge) et
 * les vérifications d'unicité d'email.
 *
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" non supportées.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function save(object $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(object $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Vérifie si un email est déjà utilisé, en excluant optionnellement un utilisateur.
     *
     * @param string   $email     Email à vérifier
     * @param int|null $excludeId Identifiant à exclure de la vérification (mise à jour de profil)
     */
    public function existsByEmail(string $email, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.email = :email')
            ->setParameter('email', $email);

        if ($excludeId) {
            $qb->andWhere('u.id != :id')
                ->setParameter('id', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function findAllDeleted(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.deletedAt IS NOT NULL')
            ->orderBy('u.deletedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les comptes ayant demandé la suppression et déjà soft-deleted.
     * Utilisé par le tableau de bord admin pour confirmer les suppressions.
     *
     * @return User[]
     */
    public function findPendingDeletion(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.deletionRequestedAt IS NOT NULL')
            ->andWhere('u.deletedAt IS NOT NULL')
            ->orderBy('u.deletionRequestedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countActiveUsers(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.deletedAt IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countDeletedUsers(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.deletedAt IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Retourne les comptes soft-deleted depuis plus d'1 mois, éligibles à la purge définitive.
     *
     * @return User[]
     */
    public function findPurgeable(): array
    {
        $threshold = new \DateTimeImmutable('-1 month');

        return $this->createQueryBuilder('u')
            ->where('u.deletedAt IS NOT NULL')
            ->andWhere('u.deletedAt < :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}