<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json(
            $this->userService->getAll(),
            context: ['groups' => ['user:read']]
        );
    }

    #[Route('/{id}', methods: ['GET'])]
    public function getOne(int $id): JsonResponse
    {
        return $this->json(
            $this->userService->getById($id),
            context: ['groups' => ['user:read']]
        );
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $userData = new User();

        if (isset($data['roles'])) {
            $userData->setRoles($data['roles']);
        }

        if (isset($data['isVerified'])) {
            $userData->setIsVerified($data['isVerified']);
        }

        $updated = $this->userService->update($id, $userData);

        return $this->json($updated, context: ['groups' => ['user:read']]);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->userService->delete($id);
        return $this->json(['message' => 'Utilisateur supprimé']);
    }
}
