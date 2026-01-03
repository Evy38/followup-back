<?php

namespace App\Controller\Auth;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegisterController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserService $userService,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'])) {
            return new JsonResponse([
                'error' => 'Email et mot de passe requis.'
            ], Response::HTTP_BAD_REQUEST);
        }


        $email = strtolower(trim($data['email']));
        $user = new User();
        $user->setEmail($email);
        if (!empty($data['firstName'] ?? null)) {
            $user->setFirstName($data['firstName']);
        }
        if (!empty($data['lastName'] ?? null)) {
            $user->setLastName($data['lastName']);
        }
        $user->setPassword($data['password']);
        $user->setRoles(['ROLE_USER']);

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse($errorMessages, Response::HTTP_BAD_REQUEST);
        }

        // Toute la logique métier est déléguée à UserService
        $userService->create($user);

        return new JsonResponse([
            'message' => 'Utilisateur créé avec succès',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ]
        ], Response::HTTP_CREATED);
    }
}