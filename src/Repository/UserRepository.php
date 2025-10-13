<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;   # Methodes doctrine standard (find, findAll, findBy, findOneBy...)
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

     /**
     * Retourne tous les utilisateurs
     */
    public function findAllUsers(): array
    {
        return $this->findAll(); // méthode héritée de Doctrine
    }

    /**
     * Trouve un utilisateur par son ID
     */
    public function findUserById(int $id): ?User
    {
        return $this->find($id);
    }

    /**
     * Ajoute un utilisateur en base
     */
    public function addUser(User $user): void
    {
        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();
    }

    /**
     * Met à jour un utilisateur existant
     */
    public function updateUser(User $user): void
    {
        $em = $this->getEntityManager();
        $em->flush(); // Doctrine détecte les changements
    }

    /**
     * Supprime un utilisateur
     */
    public function deleteUser(User $user): void
    {
        $em = $this->getEntityManager();
        $em->remove($user);
        $em->flush();
    }
}
