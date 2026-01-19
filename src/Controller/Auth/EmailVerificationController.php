<?php

namespace App\Controller\Auth;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;


class EmailVerificationController extends AbstractController
{
    #[Route('/api/verify-email', name: 'api_verify_email', methods: ['GET'])]
    public function verifyEmail(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $token = $request->query->get('token');

        if (!$token) {
            return new JsonResponse([
                'error' => 'Token manquant.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->findOneBy([
            'emailVerificationToken' => $token
        ]);

        if (!$user) {
            return new JsonResponse([
                'error' => 'Token invalide.'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$user->isEmailVerificationTokenValid()) {
            return new JsonResponse([
                'error' => 'Token expiré.'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Activation du compte
        $user->setIsVerified(true);
        $user->setEmailVerificationToken(null);
        $user->setEmailVerificationTokenExpiresAt(null);
        
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Adresse email confirmée avec succès.'
        ], Response::HTTP_OK);
    }
}
