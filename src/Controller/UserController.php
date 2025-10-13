<?php

namespace App\Controller;

use App\Entity\User;
use App\Services\UserService; # une autre couche metier --> délégation au service pour que le controller reste mince
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; #fournit les helpers
use Symfony\Component\HttpFoundation\JsonResponse; #http sorties
use Symfony\Component\HttpFoundation\Request; #http entrées
use Symfony\Component\Routing\Annotation\Route; #anotation des routes
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[Route('/api/users')] # préfix pour toute les routes
class UserController extends AbstractController # Hérite de methodes utiles
{
    public function __construct(
        private readonly UserService $service,
        private readonly ValidatorInterface $validator
    ) #injection des dépendances
    {}

    ############################################################################
    ###################### voir tout les users READ ############################

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $users = $this->service->getAll();
        // Ici, on renvoie avec le groupe "user:read"
        return $this->json($users, 200, [], ['groups' => ['user:read']]);
    }


    ############################################################################
    ###################### # voir un seul user READ ############################

    #[Route('/{id}', methods: ['GET'])] 
    public function show(int $id): JsonResponse #symfony convertie {id} en int
    {
        $user = $this->service->getById($id);
        return $this->json($user, 200, [], ['groups' => ['user:read']]);
    }

    ############################################################################
    ###################### Créer un user CREATE ################################

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $user = new User();
        $user->setEmail($data['email'] ?? '');
        $user->setRoles($data['roles'] ?? ['ROLE_USER']);
        $user->setPassword($data['password'] ?? '');

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], 400);
        }

        $created = $this->service->create($user);

        return $this->json($created, 201, [], ['groups' => ['user:read']]);
    }

    ############################################################################
    ############### Mettre à jour un user UPDATE ###############################

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $patch = new User();
        if (isset($data['email'])) $patch->setEmail($data['email']);
        if (isset($data['roles'])) $patch->setRoles($data['roles']);
        if (isset($data['password'])) $patch->setPassword($data['password']);

        $updated = $this->service->update($id, $patch);

        return $this->json($updated, 200, [], ['groups' => ['user:read']]);

    }

    ############################################################################
    ################ supprimer un user DELETE ##################################

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->service->delete($id);
        return $this->json(['message' => 'Utilisateur supprimé'], 204);
    }
}
