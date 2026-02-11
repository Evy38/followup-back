<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Contrôleur d'inscription utilisateur.
 * 
 * Workflow :
 * 1. Validation des données d'entrée
 * 2. Création de l'utilisateur via UserService
 * 3. Envoi automatique de l'email de vérification
 */
class RegisterController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly ValidatorInterface $validator
    ) {
    }

    /**
     * Inscription d'un nouvel utilisateur.
     * 
     * Payload attendu :
     * - email (requis, format Gmail)
     * - password (requis, min 8 caractères, 1 majuscule, 1 chiffre)
     * - firstName (optionnel)
     * - lastName (optionnel)
     * 
     * @param Request $request Contient les données d'inscription
     * @return JsonResponse Message de succès avec statut HTTP 201
     * 
     * @throws BadRequestHttpException Si les données sont invalides
     * @throws ConflictHttpException Si l'email existe déjà
     */
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // ✅ 1. Vérification payload
        if (!is_array($data) || !isset($data['email'], $data['password'])) {
            return new JsonResponse(
                ['message' => 'Email et mot de passe requis.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // ✅ 2. Construction entité
        $user = new User();
        $user->setEmail(strtolower(trim($data['email'])));
        $user->setPassword($data['password']);
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(false);

        // RGPD : optionnel pour les tests
        if (($data['consentRgpd'] ?? false) === true) {
            $user->setConsentRgpd(true);
            $user->setConsentRgpdAt(new \DateTimeImmutable());
        }

        if (!empty($data['firstName'] ?? null)) {
            $user->setFirstName($data['firstName']);
        }

        if (!empty($data['lastName'] ?? null)) {
            $user->setLastName($data['lastName']);
        }

        // ✅ 3. Validation
        $errors = $this->validator->validate($user);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return new JsonResponse($errorMessages, Response::HTTP_BAD_REQUEST);
        }

        // ✅ 4. Création
        try {
            $this->userService->create($user);

            return new JsonResponse([
                'message' => 'Compte créé avec succès. Veuillez confirmer votre adresse email pour activer votre compte.'
            ], Response::HTTP_CREATED);

        } catch (ConflictHttpException $e) {
            return new JsonResponse(
                ['message' => $e->getMessage()],
                Response::HTTP_CONFLICT
            );

        } catch (\Throwable $e) {
            return new JsonResponse(
                ['message' => 'Une erreur est survenue lors de la création du compte. Veuillez réessayer.'],
                Response::HTTP_BAD_REQUEST
            );
        }
    }

}