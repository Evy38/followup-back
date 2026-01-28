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
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

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

        // 1) Entreprise (find or create)
        $entreprise = $entrepriseRepository->findOneByNom($dto->company);
        if (!$entreprise) {
            $entreprise = new Entreprise();
            $entreprise->setNom($dto->company);
            $em->persist($entreprise);
        }

        // 2) Statut par défaut (doit exister)
        $statut = $statutRepository->findOneBy(['libelle' => 'Candidaté']);
        if (!$statut) {
            // MVP rapide : on le crée si absent (sinon fais-le via fixtures/migration)
            $statut = new \App\Entity\Statut();
            $statut->setLibelle('Candidaté');
            $em->persist($statut);
        }

        // 3) Empêcher doublon : même user + même lienAnnonce
        // (tu peux raffiner plus tard avec externalId stocké)
        $existing = $em->getRepository(Candidature::class)->findOneBy([
            'user' => $user,
            'lienAnnonce' => $dto->redirectUrl,
        ]);

        if ($existing) {
            return $this->json($existing, 200, [], ['groups' => ['candidature:read']]);
        }

        // 4) Créer la candidature
        $candidature = new Candidature();
        $candidature->setUser($user);
        $candidature->setEntreprise($entreprise);
        $candidature->setStatut($statut);
        $candidature->setDateCandidature(new \DateTime());
        $candidature->setLienAnnonce($dto->redirectUrl);
        $candidature->setMode('externe'); // ou null si tu veux
        $candidature->setCommentaire(null);
        $candidature->setExternalOfferId($dto->externalId);
        $candidature->setJobTitle($dto->title ?: 'Poste non renseigné');

        $em->persist($candidature);

        // 5) Créer automatiquement les relances (+7, +14, +21 jours)
        $baseDate = new \DateTimeImmutable();

        $delays = [7, 14, 21];

        foreach ($delays as $delay) {
            $relance = new \App\Entity\Relance();
            $relance->setCandidature($candidature);
            $relance->setDateRelance($baseDate->modify("+$delay days"));

            $em->persist($relance);
        }
        $em->flush();

        return $this->json($candidature, 201, [], ['groups' => ['candidature:read']]);
    }
}
