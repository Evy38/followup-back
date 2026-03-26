<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class LogoutController extends AbstractController
{
    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function logout(): JsonResponse
    {
        $response = new JsonResponse(['message' => 'Déconnexion réussie.'], Response::HTTP_OK);

        $response->headers->setCookie(Cookie::create('access_token')
            ->withValue('')
            ->withExpires(new \DateTimeImmutable('2000-01-01'))
            ->withHttpOnly(true)
            ->withPath('/')
        );

        $response->headers->setCookie(Cookie::create('refresh_token')
            ->withValue('')
            ->withExpires(new \DateTimeImmutable('2000-01-01'))
            ->withHttpOnly(true)
            ->withPath('/')
        );

        return $response;
    }
}
