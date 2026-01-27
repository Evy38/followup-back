<?php

namespace App\Service;

use App\Entity\Relance;
use App\Repository\StatutRepository;
use Doctrine\ORM\EntityManagerInterface;

class RelanceService
{
    private EntityManagerInterface $em;
    private StatutRepository $statutRepository;

    public function __construct(
        EntityManagerInterface $em,
        StatutRepository $statutRepository
    ) {
        $this->em = $em;
        $this->statutRepository = $statutRepository;
    }

    /**
     * À appeler juste avant de persist/flush une Relance.
     * Met à jour la candidature liée : compteur, date dernière relance, statut.
     */
    public function onRelanceCreated(Relance $relance): void
    {
        $candidature = $relance->getCandidature();
        if (!$candidature) {
            return;
        }

        // 1) Compteur
        $candidature->incrementNbRelances();

        // 2) Date dernière relance
        $dateRelance = $relance->getDateRelance();
        if ($dateRelance) {
            $candidature->setDateDerniereRelance($dateRelance);
        }

        // 3) Statut = "Relancée" (si présent en base)
        $statutRelance = $this->statutRepository->findOneBy(['libelle' => 'Relancée']);
        if ($statutRelance) {
            $candidature->setStatut($statutRelance);
        }

        // Pas de flush ici : le flush doit rester dans le processor/controller
        $this->em->persist($candidature);
    }
}
