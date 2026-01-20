<?php

namespace App\Controller\Auth;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ForgotPasswordController extends AbstractController
{

    /**
     * Demande de réinitialisation de mot de passe - envoie un email avec un lien
     */
    #[Route('/api/password/request', name: 'api_password_request', methods: ['POST'])]
    public function requestPasswordReset(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['email'])) {
            return new JsonResponse([
                'error' => "L'adresse email est requise."
            ], Response::HTTP_BAD_REQUEST);
        }

        $email = strtolower(trim((string) $data['email']));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse([
                'error' => "Adresse email invalide."
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->findOneBy(['email' => $email]);
        
        // Pour des raisons de sécurité, on renvoie toujours le même message
        // même si l'utilisateur n'existe pas
        if (!$user) {
            return new JsonResponse([
                'message' => 'Si un compte existe avec cet email, un lien de réinitialisation a été envoyé.'
            ], Response::HTTP_OK);
        }

        // Générer un token sécurisé
        $token = bin2hex(random_bytes(32));
        $expiresAt = new \DateTimeImmutable('+1 hour');

        $user->setResetPasswordToken($token);
        $user->setResetPasswordTokenExpiresAt($expiresAt);
        
        $em->flush();

        // Construire l'URL de réinitialisation (à adapter selon votre front-end)
        $resetUrl = $this->getParameter('frontend_url') . '/reset-password?token=' . $token;

        // Envoyer l'email
        $emailMessage = (new TemplatedEmail())
            ->from(new Address('noreply@followup.com', 'FollowUp'))
            ->to(new Address($user->getEmail()))
            ->subject('Réinitialisation de votre mot de passe - FollowUp')
            ->htmlTemplate('emails/forgot_password.html.twig')
            ->context([
                'user' => $user,
                'resetUrl' => $resetUrl,
            ]);

        try {
            $mailer->send($emailMessage);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => "Erreur lors de l'envoi de l'email. Veuillez réessayer plus tard."
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse([
            'message' => 'Si un compte existe avec cet email, un lien de réinitialisation a été envoyé.'
        ], Response::HTTP_OK);
    }

    /**
     * Réinitialisation du mot de passe avec le token
     */
    #[Route('/api/password/reset', name: 'api_password_reset', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['token'], $data['newPassword'])) {
            return new JsonResponse([
                'error' => "Token et nouveau mot de passe requis."
            ], Response::HTTP_BAD_REQUEST);
        }

        $token = (string) $data['token'];
        $newPassword = (string) $data['newPassword'];
        $confirmPassword = $data['confirmPassword'] ?? null;

        // Validation du mot de passe
        if ($confirmPassword !== null && $newPassword !== (string) $confirmPassword) {
            return new JsonResponse([
                'error' => "Les mots de passe ne correspondent pas."
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!preg_match('/^(?=.*[A-Z])(?=.*\d).{8,}$/', $newPassword)) {
            return new JsonResponse([
                'error' => "Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre."
            ], Response::HTTP_BAD_REQUEST);
        }

        // Rechercher l'utilisateur par le token
        $user = $userRepository->findOneBy(['resetPasswordToken' => $token]);
        
        if (!$user) {
            return new JsonResponse([
                'error' => "Token invalide ou expiré."
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si le token est expiré
        if (!$user->isResetPasswordTokenValid()) {
            return new JsonResponse([
                'error' => "Token expiré. Veuillez faire une nouvelle demande de réinitialisation."
            ], Response::HTTP_BAD_REQUEST);
        }

        // Changer le mot de passe
        $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        
        // Supprimer le token utilisé
        $user->setResetPasswordToken(null);
        $user->setResetPasswordTokenExpiresAt(null);

        $em->flush();
        error_log('RESET TOKEN GENERATED: ' . $token);


        return new JsonResponse([
            'message' => 'Mot de passe mis à jour avec succès.'
        ], Response::HTTP_OK);
    }
}
