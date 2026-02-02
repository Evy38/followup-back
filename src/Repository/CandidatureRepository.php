<?php

namespace App\Repository;

use App\Entity\Candidature;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CandidatureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Candidature::class);
    }
    public function findWithReponseRecruteur($user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.statutReponse != :attente')
            ->setParameter('user', $user)
            ->setParameter('attente', 'attente')
            ->orderBy('c.dateCandidature', 'DESC')
            ->getQuery()
            ->getResult();
    }
}