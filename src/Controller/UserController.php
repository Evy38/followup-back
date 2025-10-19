<?php

namespace App\Controller;

use App\Entity\User;
use App\Services\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/users')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly UserService $service,       // couche métier (logique)
        private readonly ValidatorInterface $validator // validation des entrées
    ) {}

    /**
     * 🔹 GET /api/users → liste tous les utilisateurs
     */
    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $users = $this->service->getAll();
        return $this->json($users, 200, [], ['groups' => ['user:read']]);
    }

    /**
     * 🔹 GET /api/users/{id} → affiche un utilisateur précis
     */
    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->service->getById($id);
        return $this->json($user, 200, [], ['groups' => ['user:read']]);
    }

    /**
     * 🔹 POST /api/users → crée un utilisateur
     */
    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // On récupère le JSON envoyé dans la requête
        $data = json_decode($request->getContent(), true) ?? [];

        // On crée un nouvel utilisateur
        $user = new User();
        $user->setEmail($data['email'] ?? '');
        $user->setRoles($data['roles'] ?? ['ROLE_USER']);
        $user->setPassword($data['password'] ?? '');

        // Validation des contraintes (avec groupe "create" pour le mot de passe)
        $errors = $this->validator->validate($user, null, ['create']);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], 400);
        }

        // Appel de la couche métier
        $created = $this->service->create($user);

        // Réponse 201 Created
        return $this->json($created, 201, [], ['groups' => ['user:read']]);
    }

    /**
     * 🔹 PATCH /api/users/{id} → met à jour les infos d’un utilisateur
     */
    #[Route('/{id}', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        // On prépare les données partielles
        $patch = new User();
        if (isset($data['email'])) $patch->setEmail($data['email']);
        if (isset($data['roles'])) $patch->setRoles($data['roles']);
        if (isset($data['password'])) $patch->setPassword($data['password']);

        $updated = $this->service->update($id, $patch);

        return $this->json($updated, 200, [], ['groups' => ['user:read']]);
    }

    /**
     * 🔹 DELETE /api/users/{id} → supprime un utilisateur
     */
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->service->delete($id);
        // 204 No Content = suppression réussie, pas de corps JSON
        return new JsonResponse(null, 204);
    }
}
