<?php

namespace App\Controller\Auth;

use App\Repository\UserRepository;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Gère la vérification d'adresse email après inscription ou changement d'email.
 *
 * Endpoints :
 * - GET  /api/verify-email         Confirme l'email via le token reçu par mail
 * - POST /api/verify-email/resend  Renvoie l'email de confirmation
 *
 * Deux cas couverts par GET /api/verify-email :
 * - Inscription : active le compte (`isVerified = true`)
 * - Changement d'email : applique `pendingEmail` comme email principal
 */
class VerifyEmailController extends AbstractController
{
    #[Route('/api/verify-email', name: 'api_verify_email', methods: ['POST'])]
    public function verifyEmail(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $token = $request->toArray()['token'] ?? null;

        if (!$token) {
            return new JsonResponse(['error' => 'Token manquant'], 400);
        }

        $user = $userRepository->findOneBy([
            'emailVerificationToken' => $token
        ]);

        if (!$user || $user->isDeleted() || !$user->isEmailVerificationTokenValid()) {
            return new JsonResponse(['message' => 'Le lien de vérification est invalide ou a expiré. Veuillez en demander un nouveau.'], 400);
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
        EmailVerificationService $emailVerificationService,
        RateLimiterFactoryInterface $resendVerificationLimiter
    ): JsonResponse {
        $limiter = $resendVerificationLimiter->create($request->getClientIp());
        if (!$limiter->consume(1)->isAccepted()) {
            return new JsonResponse(
                ['message' => 'Trop de tentatives. Réessayez dans quelques minutes.'],
                Response::HTTP_TOO_MANY_REQUESTS
            );
        }

        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return new JsonResponse(['message' => 'Email manquant.'], 400);
        }

        $user = $userRepository->findOneBy([
            'email' => strtolower(trim($email))
        ]);

        if (!$user || $user->isDeleted() || $user->isVerified()) {
            return new JsonResponse([
                'message' => 'Si un compte non confirmé existe avec cet email, un nouveau lien a été envoyé.'
            ], 200);
        }

        $emailVerificationService->generateVerificationToken($user);

        $em->flush();

        $emailVerificationService->sendVerificationEmail($user);

        return new JsonResponse([
            'message' => 'Email de confirmation renvoyé.'
        ], 200);
    }
}