<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Relance;
use App\Repository\StatutRepository;
use Doctrine\ORM\EntityManagerInterface;

final class RelanceCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $persistProcessor,
        private readonly EntityManagerInterface $em,
        private readonly StatutRepository $statutRepository,
    ) {}

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): mixed {
        if (!$data instanceof Relance) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        // Date par défaut
        if (!$data->getDateRelance()) {
            $data->setDateRelance(new \DateTimeImmutable());
        }

        $candidature = $data->getCandidature();

        if ($candidature) {
            // Date dernière relance
            $candidature->setDateDerniereRelance($data->getDateRelance());

            // Statut = Relancée
            $statutRelance = $this->statutRepository->findOneBy([
                'libelle' => 'Relancée'
            ]);

            if ($statutRelance) {
                $candidature->setStatut($statutRelance);
            }

            $this->em->persist($candidature);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
