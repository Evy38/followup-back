<?php

namespace App\Controller\Auth;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contrôleur de réinitialisation de mot de passe oublié.
 * 
 * Workflow sécurisé :
 * 1. Génération d'un token unique à usage unique (validité 1h)
 * 2. Envoi d'un email avec le lien de réinitialisation
 * 3. Validation du token et changement du mot de passe
 * 
 * Mesures de sécurité :
 * - Réponse identique que l'utilisateur existe ou non (timing attack)
 * - Token cryptographiquement sécurisé (64 caractères hexadécimaux)
 * - Expiration automatique après 1h
 * - Suppression du token après utilisation
 */
class ForgotPasswordController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $em,
        private readonly MailerInterface $mailer,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    /**
     * Demande de réinitialisation de mot de passe.
     * 
     * Génère un token et envoie un email avec le lien de réinitialisation.
     * Pour des raisons de sécurité, renvoie toujours le même message
     * (même si l'utilisateur n'existe pas).
     * 
     * @param Request $request Contient l'email de l'utilisateur
     * @return JsonResponse Message de confirmation générique
     */
    #[Route('/api/password/request', name: 'api_password_request', methods: ['POST'])]
    public function requestPasswordReset(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['email'])) {
            throw new BadRequestHttpException('L\'adresse email est requise.');
        }

        $email = strtolower(trim((string) $data['email']));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new BadRequestHttpException('Adresse email invalide.');
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);

        // Pour éviter l'énumération d'utilisateurs (timing attack),
        // on renvoie toujours le même message
        $genericMessage = 'Si un compte existe avec cet email, un lien de réinitialisation a été envoyé.';

        if (!$user) {
            return new JsonResponse(['message' => $genericMessage], Response::HTTP_OK);
        }

        // Génération d'un token cryptographiquement sécurisé
        $token = bin2hex(random_bytes(32));
        $expiresAt = new \DateTimeImmutable('+1 hour');

        $user->setResetPasswordToken($token);
        $user->setResetPasswordTokenExpiresAt($expiresAt);

        $this->em->flush();

        // Construction de l'URL de réinitialisation
        $resetUrl = $this->getParameter('frontend_url') . '/reset-password?token=' . $token;

        // Envoi de l'email
        try {
            $this->sendResetEmail($user->getEmail(), $user, $resetUrl);
        } catch (TransportExceptionInterface $e) {
            // On log l'erreur mais on ne révèle pas au client que l'envoi a échoué
            $this->container->get('logger')->error(
                'Échec envoi email réinitialisation : ' . $e->getMessage(),
                ['email' => $user->getEmail()]
            );
        }

        return new JsonResponse(['message' => $genericMessage], Response::HTTP_OK);
    }

    /**
     * Réinitialisation du mot de passe avec le token.
     * 
     * Valide le token, vérifie l'expiration, et change le mot de passe.
     * Le token est supprimé après utilisation.
     * 
     * @param Request $request Contient le token et le nouveau mot de passe
     * @return JsonResponse Message de succès
     * 
     * @throws BadRequestHttpException Si le token est invalide/expiré ou le mot de passe non conforme
     */
    #[Route('/api/password/reset', name: 'api_password_reset', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['token'], $data['newPassword'])) {
            throw new BadRequestHttpException('Le token et le nouveau mot de passe sont requis.');
        }

        $token = (string) $data['token'];
        $newPassword = (string) $data['newPassword'];
        $confirmPassword = $data['confirmPassword'] ?? null;

        // Validation de la correspondance des mots de passe
        if ($confirmPassword !== null && $newPassword !== (string) $confirmPassword) {
            throw new BadRequestHttpException('Les mots de passe ne correspondent pas.');
        }

        // Validation de la complexité du mot de passe
        if (!$this->isPasswordValid($newPassword)) {
            throw new BadRequestHttpException(
                'Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.'
            );
        }

        // Recherche de l'utilisateur par le token
        $user = $this->userRepository->findOneBy(['resetPasswordToken' => $token]);

        if (!$user || !$user->isResetPasswordTokenValid()) {
            throw new BadRequestHttpException('Token invalide ou expiré.');
        }

        // Changement du mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);

        // Suppression du token utilisé (usage unique)
        $user->setResetPasswordToken(null);
        $user->setResetPasswordTokenExpiresAt(null);

        $this->em->flush();

        return new JsonResponse([
            'message' => 'Mot de passe mis à jour avec succès.'
        ], Response::HTTP_OK);
    }

    /**
     * Valide la complexité du mot de passe.
     * 
     * Règles :
     * - Minimum 8 caractères
     * - Au moins 1 majuscule
     * - Au moins 1 chiffre
     */
    private function isPasswordValid(string $password): bool
    {
        return (bool) preg_match('/^(?=.*[A-Z])(?=.*\d).{8,}$/', $password);
    }

    /**
     * Envoie l'email de réinitialisation de mot de passe.
     * 
     * @throws TransportExceptionInterface Si l'envoi échoue
     */
    private function sendResetEmail(string $emailAddress, object $user, string $resetUrl): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('noreply@followup.com', 'FollowUp'))
            ->to(new Address($emailAddress))
            ->subject('Réinitialisation de votre mot de passe - FollowUp')
            ->htmlTemplate('emails/forgot_password.html.twig')
            ->context([
                'user' => $user,
                'resetUrl' => $resetUrl,
            ]);

        $this->mailer->send($email);
    }
}