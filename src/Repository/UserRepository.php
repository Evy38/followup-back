<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * Repository = couche d’accès aux données
 * Permet de manipuler la base de données sans écrire de SQL.
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * 🔁 Méthode Symfony : met à jour le hash du mot de passe si nécessaire.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" non supportées.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * ✅ Méthode générique : sauvegarde (création ou mise à jour)
     * $flush = true => exécute tout de suite la requête SQL
     * $flush = false => enregistre dans le cache Doctrine, mais n’envoie pas encore à la BDD
     */
    public function save(object $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * ✅ Supprime un utilisateur
     */
    public function remove(object $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 🔍 Trouve un utilisateur par son email
     */
    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * 🧩 Vérifie si un email existe déjà dans la BDD
     * Si $excludeId est donné → ignore cet utilisateur (utile en mode "update")
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

        // getSingleScalarResult() renvoie un nombre → on le convertit en booléen
        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
