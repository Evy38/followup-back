<?php

namespace App\Controller\Api\User;

use App\Entity\User;
use App\Service\UserService;
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
    #[IsGranted('ROLE_USER')]
    public function getProfile(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }
        return $this->json($user, context: ['groups' => ['user:read']]);

    }

    // Modifier le profil
    #[Route('/profile', name: 'api_user_update_profile', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function updateProfile(Request $request, UserService $userService): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'JSON invalide'], 400);
        }

        $userData = new User();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        if (isset($data['email'])) {
            $userData->setEmail($data['email']);
        }
        if (isset($data['firstName'])) {
            $userData->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $userData->setLastName($data['lastName']);
        }
        if (isset($data['password'])) {
            $userData->setPassword($data['password']);
        }
        $updatedUser = $userService->update($user->getId(), $userData);
        return $this->json($updatedUser, context: ['groups' => ['user:read']]);
    }

    // Liste des utilisateurs (admin uniquement)
    #[Route('', name: 'api_users_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function list(UserService $userService): JsonResponse
    {
        $users = $userService->getAll();
        return $this->json($users, context: ['groups' => ['user:read']]);
    }
}