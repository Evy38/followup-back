<?php

namespace App\Controller\Api\User;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/user')]
class UserController extends AbstractController
{
    // Profil de l'utilisateur connecté
    #[Route('/profile', name: 'api_user_profile', methods: ['GET'])]
    public function getProfile(): JsonResponse
    {
        return $this->json($this->getUser());
    }

    // Modifier le profil
    #[Route('/profile', name: 'api_user_update_profile', methods: ['PUT'])]
    public function updateProfile(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        
        // Logique de mise à jour
        
        return $this->json($user);
    }

    // Liste des utilisateurs (admin uniquement)
    #[Route('', name: 'api_users_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function list(): JsonResponse
    {
        // Logique pour lister les utilisateurs
    }
}