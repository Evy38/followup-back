<?php

namespace App\Controller\Api;

use App\DTO\CreateCandidatureFromOfferDTO;
use App\Entity\Candidature;
use App\Entity\Entreprise;
use App\Entity\User;
use App\Entity\Statut;
use App\Enum\StatutReponse;
use App\Repository\CandidatureRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\StatutRepository;
use App\Service\RelanceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/candidatures')]
#[IsGranted('ROLE_USER')]
class CandidatureFromOfferController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EntrepriseRepository $entrepriseRepository,
        private readonly StatutRepository $statutRepository,
        private readonly CandidatureRepository $candidatureRepository,
        private readonly RelanceService $relanceService
    ) {
    }

    #[Route('/from-offer', name: 'api_candidatures_from_offer', methods: ['POST'])]
    public function createFromOffer(
        Request $request,
        \Symfony\Component\Validator\Validator\ValidatorInterface $validator
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isDeleted()) {
            throw new AccessDeniedHttpException('Ce compte est supprimé.');
        }

        $data = json_decode($request->getContent(), true);
        $dto = new CreateCandidatureFromOfferDTO();
        $dto->externalId = $data['externalId'] ?? '';
        $dto->company = $data['company'] ?? '';
        $dto->redirectUrl = $data['redirectUrl'] ?? '';
        $dto->title = $data['title'] ?? null;
        $dto->location = $data['location'] ?? null;

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()][] = $error->getMessage();
            }
            return $this->json([
                'message' => 'Validation failed',
                'errors' => $messages
            ], 422);
        }

        $existing = $this->candidatureRepository->findOneBy([
            'user' => $user,
            'lienAnnonce' => $dto->redirectUrl,
        ]);

        if ($existing) {
            return $this->json($existing, 200, [], ['groups' => ['candidature:read']]);
        }

        $entreprise = $this->getOrCreateEntreprise($dto->company);
        $statut = $this->getStatutCandidature();
        $candidature = $this->createCandidature($user, $entreprise, $statut, $dto);
        $this->attachRelances($candidature);

        $this->em->persist($candidature);
        $this->em->flush();

        return $this->json($candidature, 201, [], ['groups' => ['candidature:read']]);
    }

    private function getOrCreateEntreprise(string $nomEntreprise): Entreprise
    {
        $entreprise = $this->entrepriseRepository->findOneByNom($nomEntreprise);

        if (!$entreprise) {
            $entreprise = new Entreprise();
            $entreprise->setNom($nomEntreprise);
            $this->em->persist($entreprise);
        }

        return $entreprise;
    }

    private function getStatutCandidature(): Statut
    {
        $statut = $this->statutRepository->findOneBy(['libelle' => 'Envoyée']);

        if (!$statut) {
            throw new BadRequestHttpException(
                'Le statut "Envoyée" n\'existe pas en base. Vérifiez les fixtures.'
            );
        }

        return $statut;
    }

    private function createCandidature(
        User $user,
        Entreprise $entreprise,
        Statut $statut,
        CreateCandidatureFromOfferDTO $dto
    ): Candidature {
        $candidature = new Candidature();
        $candidature->setUser($user);
        $candidature->setEntreprise($entreprise);
        $candidature->setStatut($statut);
        $candidature->setDateCandidature(new \DateTimeImmutable());
        $candidature->setLienAnnonce($dto->redirectUrl);
        $candidature->setMode('externe');
        $candidature->setExternalOfferId($dto->externalId);
        $candidature->setJobTitle($dto->title ?: 'Poste non renseigné');
        $candidature->setStatutReponse(StatutReponse::ATTENTE);

        return $candidature;
    }

    private function attachRelances(Candidature $candidature): void
    {
        $relances = $this->relanceService->createDefaultRelances($candidature);

        foreach ($relances as $relance) {
            $candidature->addRelance($relance);
        }
    }
}