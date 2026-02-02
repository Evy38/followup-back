<?php

namespace App\Controller\Auth;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\EmailVerificationService;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Service\UserService;


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
        $user->setIsVerified(false);
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

        try {
            // Toute la logique métier est déléguée à UserService
            $userService->create($user);

            return new JsonResponse([
                'message' => 'Compte créé. Veuillez confirmer votre adresse email pour activer votre compte.'
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur serveur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }
}