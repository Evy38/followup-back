<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Entretien;
use App\Service\CandidatureStatutSyncService;
use Doctrine\ORM\EntityManagerInterface;

class EntretienProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private CandidatureStatutSyncService $statutSyncService
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Entretien) {
            return $data;
        }

        $candidature = $data->getCandidature();

        // CAS DELETE
        if ($operation instanceof \ApiPlatform\Metadata\Delete) {
            $this->em->remove($data);
            $this->em->flush();

            $this->statutSyncService->syncAfterEntretienDeletion($candidature);
            $this->em->flush();

            return null;
        }

        // CAS POST / PATCH
        $this->em->persist($data);
        $this->em->flush();

        $this->statutSyncService->syncFromEntretien($candidature);
        $this->em->flush();

        return $data;
    }

}
