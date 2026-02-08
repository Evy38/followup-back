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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur dédié à la création de candidatures depuis une offre externe.
 * 
 * Gère la création de candidatures provenant d'APIs tierces (ex: Adzuna)
 * avec génération automatique des relances.
 */
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

    /**
     * Crée une candidature depuis une offre externe.
     * 
     * Workflow :
     * 1. Validation du DTO (via MapRequestPayload)
     * 2. Vérification anti-doublon (même user + même URL)
     * 3. Récupération ou création de l'entreprise
     * 4. Récupération du statut "Candidaté"
     * 5. Création de la candidature
     * 6. Génération automatique des 3 relances (J+7, J+14, J+21)
     * 
     * @param CreateCandidatureFromOfferDTO $dto Données de l'offre externe
     * @return JsonResponse La candidature créée ou existante
     * 
     * @throws BadRequestHttpException Si le statut "Candidaté" n'existe pas en base
     */
    #[Route('/from-offer', name: 'api_candidatures_from_offer', methods: ['POST'])]
    public function createFromOffer(Request $request, \Symfony\Component\Validator\Validator\ValidatorInterface $validator): JsonResponse
    {
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

        /** @var User $user */
        $user = $this->getUser();

        // --- Anti-doublon : même user + même lienAnnonce → renvoie l'existant ---
        $existing = $this->candidatureRepository->findOneBy([
            'user' => $user,
            'lienAnnonce' => $dto->redirectUrl,
        ]);

        if ($existing) {
            return $this->json($existing, 200, [], ['groups' => ['candidature:read']]);
        }

        // --- Récupération ou création de l'entreprise ---
        $entreprise = $this->getOrCreateEntreprise($dto->company);

        // --- Récupération du statut "Candidaté" ---
        $statut = $this->getStatutCandidature();

        // --- Création de la candidature ---
        $candidature = $this->createCandidature($user, $entreprise, $statut, $dto);

        // --- Génération des relances ---
        $this->attachRelances($candidature);

        // --- Persistance ---
        $this->em->persist($candidature);
        $this->em->flush();

        return $this->json($candidature, 201, [], ['groups' => ['candidature:read']]);
    }

    /**
     * Récupère une entreprise existante ou la crée si elle n'existe pas.
     */
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

    /**
     * Récupère le statut "Candidaté".
     * 
     * @throws BadRequestHttpException Si le statut n'existe pas en base
     */
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


    /**
     * Crée une instance de Candidature avec les données du DTO.
     */
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

    /**
     * Génère et attache les relances par défaut à la candidature.
     */
    private function attachRelances(Candidature $candidature): void
    {
        $relances = $this->relanceService->createDefaultRelances($candidature);

        foreach ($relances as $relance) {
            $candidature->addRelance($relance);
        }
    }
}