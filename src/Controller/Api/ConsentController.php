<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConsentController extends AbstractController
{
    #[Route('/api/me/consent', name: 'api_me_consent', methods: ['POST'])]
    public function consent(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user->getConsentRgpd()) {
            $user->setConsentRgpd(true);
            $user->setConsentRgpdAt(new \DateTimeImmutable());
            $em->persist($user);
            $em->flush();
        }

        return $this->json(['message' => 'Consentement enregistré'], Response::HTTP_OK);
    }
}
