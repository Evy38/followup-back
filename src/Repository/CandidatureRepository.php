<?php

namespace App\Repository;

use App\Entity\Candidature;
use App\Entity\User;
use App\Enum\StatutReponse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité Candidature.
 * 
 * Fournit des méthodes personnalisées pour récupérer des candidatures
 * selon des critères métier spécifiques.
 */
class CandidatureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Candidature::class);
    }

    /**
     * Récupère les candidatures d'un utilisateur ayant reçu une réponse du recruteur.
     * 
     * Exclut les candidatures en statut "attente" (sans réponse).
     * 
     * @param User $user L'utilisateur dont on veut récupérer les candidatures
     * @return Candidature[] Candidatures avec réponse, triées par date décroissante
     */
    public function findWithReponseRecruteur(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.statutReponse != :attente')
            ->andWhere('c.archivedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('attente', StatutReponse::ATTENTE->value)
            ->orderBy('c.dateCandidature', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les candidatures actives (non archivées) d'un utilisateur.
     *
     * @return Candidature[]
     */
    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.archivedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('c.dateCandidature', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les candidatures archivées d'un utilisateur.
     *
     * @return Candidature[]
     */
    public function findArchivedByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.archivedAt IS NOT NULL')
            ->setParameter('user', $user)
            ->orderBy('c.archivedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}