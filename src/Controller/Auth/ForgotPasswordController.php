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

class ForgotPasswordController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $em,
        private readonly MailerInterface $mailer,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

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

        $genericMessage = 'Si un compte existe avec cet email, un lien de réinitialisation a été envoyé.';

        if (!$user || $user->isDeleted()) {
            return new JsonResponse(['message' => $genericMessage], Response::HTTP_OK);
        }

        if ($user->isOauthUser()) {
            return new JsonResponse([
                'message' => 'Ce compte utilise Google pour se connecter. Aucun mot de passe n\'est défini.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = new \DateTimeImmutable('+1 hour');

        $user->setResetPasswordToken($token);
        $user->setResetPasswordTokenExpiresAt($expiresAt);

        $this->em->flush();

        $resetUrl = $this->getParameter('frontend_url') . '/reset-password?token=' . $token;

        try {
            $this->sendResetEmail($user->getEmail(), $user, $resetUrl);
        } catch (TransportExceptionInterface $e) {
            $this->container->get('logger')->error(
                'Échec envoi email réinitialisation : ' . $e->getMessage(),
                ['email' => $user->getEmail()]
            );
        }

        return new JsonResponse(['message' => $genericMessage], Response::HTTP_OK);
    }

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

        if ($confirmPassword !== null && $newPassword !== (string) $confirmPassword) {
            throw new BadRequestHttpException('Les mots de passe ne correspondent pas.');
        }

        if (!$this->isPasswordValid($newPassword)) {
            throw new BadRequestHttpException(
                'Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.'
            );
        }

        $user = $this->userRepository->findOneBy(['resetPasswordToken' => $token]);

        if (!$user || !$user->isResetPasswordTokenValid() || $user->isDeleted()) {
            throw new BadRequestHttpException('Token invalide ou expiré.');
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);

        $user->setResetPasswordToken(null);
        $user->setResetPasswordTokenExpiresAt(null);

        $this->em->flush();

        return new JsonResponse([
            'message' => 'Mot de passe mis à jour avec succès.'
        ], Response::HTTP_OK);
    }

    private function isPasswordValid(string $password): bool
    {
        return (bool) preg_match('/^(?=.*[A-Z])(?=.*\d).{8,}$/', $password);
    }

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