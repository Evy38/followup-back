<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Candidature;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Processor API Platform pour l'opération PUT sur l'entité Candidature.
 *
 * Quand `dateCandidature` est modifiée, recalcule les dates des relances
 * non encore effectuées en conservant le décalage original (J+7, J+14, J+21).
 *
 * @see \App\Entity\Candidature
 * @see \App\Entity\Relance
 */
class CandidatureUpdateProcessor implements ProcessorInterface
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
        if (!$data instanceof Candidature) {
            return $data;
        }

        $newDate = $data->getDateCandidature();

        if ($newDate !== null) {
            foreach ($data->getRelances() as $relance) {
                // Ne recalcule que les relances pas encore effectuées
                if ($relance->isFaite()) {
                    continue;
                }

                $rang = $relance->getRang();
                $joursApres = $rang * 7; // rang 1 → 7j, rang 2 → 14j, rang 3 → 21j
                $relance->setDateRelance($newDate->modify("+{$joursApres} days"));

                $this->em->persist($relance);
            }
        }

        $this->em->persist($data);
        $this->em->flush();

        return $data;
    }
}
