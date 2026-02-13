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

#[Route('/api/user')]
#[IsGranted('ROLE_USER')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    #[Route('/profile', name: 'api_user_profile', methods: ['GET'])]
    public function getProfile(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isDeleted()) {
            return $this->json([
                'error' => 'Ce compte est supprimé.'
            ], 403);
        }

        return $this->json($user, context: ['groups' => ['user:read']]);
    }

    #[Route('/profile', name: 'api_user_update_profile', methods: ['PUT'])]
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($currentUser->isDeleted()) {
            return $this->json([
                'error' => 'Ce compte est supprimé.'
            ], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            throw new BadRequestHttpException('JSON invalide.');
        }

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

        if ($user->isDeleted()) {
            return $this->json([
                'error' => 'Ce compte est supprimé.'
            ], 403);
        }

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

        if ($user->isDeleted()) {
            return $this->json([
                'error' => 'Ce compte est déjà supprimé.'
            ], 400);
        }

        $this->userService->requestDeletion($user);

        return $this->json([
            'message' => 'Votre demande de suppression a été enregistrée. Votre compte est désormais inaccessible.'
        ]);
    }
}