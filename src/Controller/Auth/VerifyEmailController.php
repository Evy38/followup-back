<?php

namespace App\Controller\Auth;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class VerifyEmailController extends AbstractController
{
    #[Route('/api/verify-email', name: 'api_verify_email', methods: ['GET'])]
    public function verifyEmail(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): JsonResponse {

        $token = $request->query->get('token');
        error_log('[VERIFY EMAIL] Token reçu : ' . $token);

        if (!$token) {
            error_log('[VERIFY EMAIL] Token manquant');
            return new JsonResponse(['error' => 'Token manquant'], 400);
        }


        $user = $userRepository->findOneBy([
            'emailVerificationToken' => $token
        ]);
        error_log('[VERIFY EMAIL] Utilisateur trouvé ? ' . ($user ? 'oui' : 'non'));

        if (!$user) {
            // Vérification idempotente : l'utilisateur a-t-il déjà été vérifié ?
            $userVerified = $userRepository->findOneBy([
                'isVerified' => true,
                'emailVerificationToken' => null
            ]);
            if ($userVerified) {
                error_log('[VERIFY EMAIL] Déjà vérifié, retour succès idempotent');
                return new JsonResponse([
                    'message' => 'Email déjà confirmé.'
                ], 200);
            }
            error_log('[VERIFY EMAIL] Token invalide');
            return new JsonResponse(['error' => 'Token invalide'], 400);
        }

        $expiresAt = $user->getEmailVerificationTokenExpiresAt();
        error_log('[VERIFY EMAIL] Expire à : ' . ($expiresAt ? $expiresAt->format('c') : 'null'));

        if (!$expiresAt || $expiresAt < new \DateTimeImmutable()) {
            error_log('[VERIFY EMAIL] Token expiré');
            return new JsonResponse(['error' => 'Token expiré'], 400);
        }

        // ✅ VALIDATION
        $user->setIsVerified(true);
        $user->setEmailVerificationToken(null);
        $user->setEmailVerificationTokenExpiresAt(null);

        $em->flush();

        return new JsonResponse([
            'message' => 'Email confirmé'
        ], 200);
    }

}
