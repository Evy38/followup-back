<?php

namespace App\Repository;

use App\Entity\Entreprise;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité Entreprise.
 *
 * @extends ServiceEntityRepository<Entreprise>
 */
class EntrepriseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Entreprise::class);
    }

    /**
     * Recherche une entreprise par son nom exact.
     * Utilisé par CandidatureFromOfferController pour éviter les doublons.
     */
    public function findOneByNom(string $nom): ?Entreprise
    {
    return $this->findOneBy(['nom' => $nom]);
}

}
