<?php

namespace App\Controller\Api;

use App\DTO\CreateCandidatureFromOfferDTO;
use App\Entity\Candidature;
use App\Entity\Entreprise;
use App\Entity\User;
use App\Repository\EntrepriseRepository;
use App\Repository\StatutRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Service\RelanceService;

#[Route('/api/candidatures')]
class CandidatureFromOfferController extends AbstractController
{
    #[Route('/from-offer', name: 'api_candidatures_from_offer', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createFromOffer(
        Request $request,
        ValidatorInterface $validator,
        EntrepriseRepository $entrepriseRepository,
        StatutRepository $statutRepository,
        RelanceService $relanceService,
        EntityManagerInterface $em
    ): JsonResponse {
        // --- Auth : récupère le user connecté ---
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        // --- Validation : lit le JSON (externalId, company, redirectUrl, title…) ---
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'JSON invalide'], 400);
        }

        $dto = new CreateCandidatureFromOfferDTO();
        $dto->externalId = (string) ($data['externalId'] ?? '');
        $dto->company = (string) ($data['company'] ?? '');
        $dto->redirectUrl = (string) ($data['redirectUrl'] ?? '');
        $dto->title = isset($data['title']) ? (string) $data['title'] : null;
        $dto->location = isset($data['location']) ? (string) $data['location'] : null;

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            $out = [];
            foreach ($errors as $e) {
                $out[$e->getPropertyPath()][] = $e->getMessage();
            }
            return $this->json($out, 400);
        }

        // --- Entreprise : find or create ---
        $entreprise = $entrepriseRepository->findOneByNom($dto->company);
        if (!$entreprise) {
            $entreprise = new Entreprise();
            $entreprise->setNom($dto->company);
            $em->persist($entreprise);
        }

        // --- Statut : find “Candidaté” (ou le crée en MVP) ---
        $statut = $statutRepository->findOneBy(['libelle' => 'Candidaté']);
        if (!$statut) {
            // MVP rapide : on le crée si absent (sinon fais-le via fixtures/migration)
            $statut = new \App\Entity\Statut();
            $statut->setLibelle('Candidaté');
            $em->persist($statut);
        }

        // --- Anti-doublon : même user + même lienAnnonce → renvoie l’existant ---
        $existing = $em->getRepository(Candidature::class)->findOneBy([
            'user' => $user,
            'lienAnnonce' => $dto->redirectUrl,
        ]);

        if ($existing) {
            return $this->json($existing, 200, [], ['groups' => ['candidature:read']]);
        }

        // --- Crée la candidature : set date, jobTitle, externalOfferId, lienAnnonce… ---
        $candidature = new Candidature();
        $candidature->setUser($user);
        $candidature->setEntreprise($entreprise);
        $candidature->setStatut($statut);
        $candidature->setDateCandidature(new \DateTimeImmutable());
        $candidature->setLienAnnonce($dto->redirectUrl);
        $candidature->setMode('externe'); // ou null si tu veux
        $candidature->setExternalOfferId($dto->externalId);
        $candidature->setJobTitle($dto->title ?: 'Poste non renseigné');

        $em->persist($candidature);

        // --- Génère les relances via RelanceService ---
        $relances = $relanceService->createDefaultRelances($candidature);

        foreach ($relances as $relance) {
            $candidature->addRelance($relance);
        }

        // --- Persist + flush : enregistre candidature + relances ---
        $em->flush();

        // --- Réponse JSON : renvoie la candidature (avec potentiellement ses relances selon sérialisation / groupes) ---
        return $this->json($candidature, 201, [], ['groups' => ['candidature:read']]);
    }
}
