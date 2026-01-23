<?php

namespace App\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;

class MeController extends AbstractController
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        error_log('====================');
        error_log('[API ME] /api/me CALLED');
        $request = $this->requestStack->getCurrentRequest();
        $authHeader = $request?->headers->get('Authorization');

        error_log('[API ME] Authorization header = ' . ($authHeader ?? 'NULL'));

        $user = $this->getUser();

        if ($user === null) {
            error_log('[API ME] getUser() = NULL');
        } else {
            error_log('[API ME] getUser() class = ' . get_class($user));
        }


        if (!$user instanceof User) {
            return $this->json([
                'authenticated' => false,
                'verified' => false,
                'user' => null,
            ], 401);
        }

        if ($user instanceof User) {
            error_log('[API ME] User ID = ' . $user->getId());
            error_log('[API ME] Email = ' . $user->getEmail());
            error_log('[API ME] isVerified = ' . ($user->isVerified() ? 'true' : 'false'));
        }

        return $this->json([
            'authenticated' => true,
            'verified' => $user->isVerified(),
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'roles' => $user->getRoles(),
                'googleId' => $user->getGoogleId(),
            ],
        ]);
    }

}
