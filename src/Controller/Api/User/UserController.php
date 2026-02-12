<?php

namespace App\Controller\Api\User;

use App\Entity\User;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de gestion des utilisateurs.
 * 
 * Endpoints :
 * - GET /api/user/profile : Profil de l'utilisateur connecté
 * - PUT /api/user/profile : Modification du profil
 * - GET /api/user : Liste des utilisateurs (admin uniquement)
 */
#[Route('/api/user')]
#[IsGranted('ROLE_USER')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService
    ) {
    }

    /**
     * Récupère le profil de l'utilisateur connecté.
     * 
     * @return JsonResponse Les données de l'utilisateur
     */
    #[Route('/profile', name: 'api_user_profile', methods: ['GET'])]
    public function getProfile(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json($user, context: ['groups' => ['user:read']]);
    }

    /**
     * Met à jour le profil de l'utilisateur connecté.
     * 
     * Champs modifiables :
     * - email
     * - firstName
     * - lastName
     * - password
     * 
     * @param Request $request Contient les données à mettre à jour
     * @return JsonResponse L'utilisateur mis à jour
     * 
     * @throws BadRequestHttpException Si le JSON est invalide
     */
    #[Route('/profile', name: 'api_user_update_profile', methods: ['PUT'])]
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            throw new BadRequestHttpException('JSON invalide.');
        }

        // Construction d'un objet User avec uniquement les champs à modifier
        $userData = new User();

        if (isset($data['email'])) {
            $userData->setEmail($data['email']);
        }

        if (isset($data['firstName'])) {
            $userData->setFirstName($data['firstName']);
        }

        if (isset($data['lastName'])) {
            $userData->setLastName($data['lastName']);
        }

        $currentPassword = $data['currentPassword'] ?? null;
        $newPassword = $data['newPassword'] ?? null;

        if ($newPassword !== null) {
            $userData->setPassword($newPassword);
        }

        // Délégation au service pour la logique métier
        $updatedUser = $this->userService->update(
            $currentUser->getId(),
            $userData,
            $currentPassword
        );


        return $this->json($updatedUser, context: ['groups' => ['user:read']]);
    }

    #[Route('/consent', methods: ['POST'])]
    public function acceptConsent(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $user->setConsentRgpd(true);
        $user->setConsentRgpdAt(new \DateTimeImmutable());

        $this->userService->save($user);

        return $this->json(['message' => 'Consentement enregistré']);
    }

    #[Route('/profile', name: 'api_user_delete_profile', methods: ['DELETE'])]
    public function deleteProfile(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $this->userService->delete($user->getId());

        return $this->json([
            'message' => 'Compte supprimé avec succès.'
        ]);
    }


}