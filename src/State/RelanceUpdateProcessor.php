<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Relance;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Processor API Platform pour les opérations PUT et PATCH sur l'entité Relance.
 *
 * Intercepte la mise à jour d'une relance pour mettre à jour automatiquement
 * le champ `dateDerniereRelance` de la candidature parente lorsqu'une relance
 * est marquée comme faite (`faite = true`).
 *
 * Ce processor est enregistré sur les opérations PUT et PATCH de Relance.
 *
 * @see \App\Entity\Relance
 */
class RelanceUpdateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): mixed {
        if (!$data instanceof Relance) {
            return $data;
        }

        // 👉 Si la relance vient d’être marquée comme faite
        if ($data->isFaite() && $data->getDateRealisation() !== null) {

            $candidature = $data->getCandidature();

            if ($candidature) {
                $candidature->setDateDerniereRelance(
                    $data->getDateRealisation()
                );

                $this->em->persist($candidature);
            }
        }

        $this->em->persist($data);
        $this->em->flush();

        return $data;
    }
}
