<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\UserService;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly UserRepository $userRepository
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $filter = $request->query->get('filter', 'active');

        $users = match ($filter) {
            'deleted' => $this->userRepository->findAllDeleted(),
            'pending' => $this->userRepository->findPendingDeletion(),
            default => $this->userService->getAll(),
        };

        return $this->json(
            $users,
            context: ['groups' => ['user:read']]
        );
    }

    #[Route('/{id}', methods: ['GET'])]
    public function getOne(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException("Utilisateur #$id introuvable.");
        }

        return $this->json(
            $user,
            context: ['groups' => ['user:read']]
        );
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            throw new BadRequestHttpException('JSON invalide.');
        }

        $user = $this->userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException("Utilisateur #$id introuvable.");
        }

        if (isset($data['roles'])) {
            $user->setRoles($data['roles']);
        }

        if (isset($data['isVerified'])) {
            $user->setIsVerified((bool) $data['isVerified']);
        }

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }

        if (isset($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }

        if (isset($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }

        $this->userService->save($user);

        return $this->json($user, context: ['groups' => ['user:read']]);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function hardDelete(int $id): JsonResponse
    {
        $this->userService->hardDelete($id);

        return $this->json([
            'message' => 'Utilisateur supprimé définitivement.'
        ]);
    }
}