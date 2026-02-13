<?php

namespace App\Controller\Auth;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\EmailVerificationService;

class VerifyEmailController extends AbstractController
{
    #[Route('/api/verify-email', name: 'api_verify_email', methods: ['GET'])]
    public function verifyEmail(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $token = $request->query->get('token');

        if (!$token) {
            return new JsonResponse(['error' => 'Token manquant'], 400);
        }

        $user = $userRepository->findOneBy([
            'emailVerificationToken' => $token
        ]);

        if (!$user || $user->isDeleted()) {
            return new JsonResponse(['error' => 'Token invalide ou expiré'], 400);
        }

        if (!$user->isEmailVerificationTokenValid()) {
            return new JsonResponse(['error' => 'Token expiré'], 400);
        }

        if ($user->getPendingEmail()) {
            $user->setEmail($user->getPendingEmail());
            $user->setPendingEmail(null);
            $user->setEmailVerificationToken(null);
            $user->setEmailVerificationTokenExpiresAt(null);

            $em->flush();

            return new JsonResponse([
                'message' => 'Nouvel email confirmé avec succès.'
            ], 200);
        }

        $user->setIsVerified(true);
        $user->setEmailVerificationToken(null);
        $user->setEmailVerificationTokenExpiresAt(null);

        $em->flush();

        return new JsonResponse([
            'message' => 'Email confirmé avec succès.'
        ], 200);
    }

    #[Route('/api/verify-email/resend', name: 'api_verify_email_resend', methods: ['POST'])]
    public function resendVerificationEmail(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        EmailVerificationService $emailVerificationService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return new JsonResponse(['message' => 'Email manquant.'], 400);
        }

        $user = $userRepository->findOneBy([
            'email' => strtolower(trim($email))
        ]);

        if (!$user || $user->isDeleted()) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
        }

        if ($user->isVerified()) {
            return new JsonResponse([
                'message' => 'Ce compte est déjà confirmé.'
            ], 400);
        }

        $emailVerificationService->generateVerificationToken($user);

        $em->flush();

        $emailVerificationService->sendVerificationEmail($user);

        return new JsonResponse([
            'message' => 'Email de confirmation renvoyé.'
        ], 200);
    }
}