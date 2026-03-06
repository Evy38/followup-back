<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Entretien;
use App\Service\CandidatureStatutSyncService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Processor API Platform pour les opérations sur l'entité Entretien.
 *
 * Intercepte les opérations POST, PATCH et DELETE sur `/api/entretiens`
 * pour garantir la cohérence de la relation bidirectionnelle avec Candidature
 * et synchroniser automatiquement le `statutReponse` de la candidature.
 *
 * Workflow :
 * - POST / PATCH : persiste l'entretien, puis appelle CandidatureStatutSyncService::syncFromEntretien()
 * - DELETE       : supprime l'entretien, puis appelle CandidatureStatutSyncService::syncAfterEntretienDeletion()
 *
 * @see \App\Service\CandidatureStatutSyncService
 */
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
        
        // Vérification que la candidature est bien définie
        if (!$candidature) {
            throw new BadRequestHttpException('La candidature est obligatoire pour créer un entretien.');
        }

        // CAS DELETE
        if ($operation instanceof \ApiPlatform\Metadata\Delete) {
            $this->em->remove($data);
            $this->em->flush();

            // Après suppression, on recalcule le statut
            $this->statutSyncService->syncAfterEntretienDeletion($candidature);
            $this->em->flush();

            return null;
        }

        // CAS POST / PATCH
        // Important : établir la relation bidirectionnelle AVANT persist
        if (!$candidature->getEntretiens()->contains($data)) {
            $candidature->addEntretien($data);
        }
        
        // Persist de l'entretien ET de la candidature (cascade)
        $this->em->persist($data);
        $this->em->persist($candidature);
        $this->em->flush();

        // Après l'enregistrement, on synchronise le statut
        $this->statutSyncService->syncFromEntretien($candidature);
        $this->em->flush();

        return $data;
    }
}