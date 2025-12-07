<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\GoogleAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    #[Route('/auth/google', name: 'auth_google')]
    public function google(GoogleAuthService $googleAuthService): RedirectResponse
    {
        $client = $googleAuthService->getClient();

        // URL de connexion Google
        $authUrl = $client->createAuthUrl();

        // Redirection vers Google
        return $this->redirect($authUrl);
    }
    #[Route('/auth/google/callback', name: 'auth_google_callback')]
    public function googleCallback(
        Request $request,
        GoogleAuthService $googleAuthService,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtManager,
    ): RedirectResponse {

        $client = $googleAuthService->getClient();
        $code = $request->query->get('code');

        if (!$code) {
            return $this->redirect('http://localhost:4200/login?error=no_code');
        }

        // Récupération du token Google
        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            return $this->redirect('http://localhost:4200/login?error=token');
        }

        // Infos user via Google
        $oauth = new \Google\Service\Oauth2($client);
        $googleUser = $oauth->userinfo->get();

        $email = $googleUser->email;
        $firstName = $googleUser->givenName;
        $lastName = $googleUser->familyName;
        $googleId = $googleUser->id;

        // Chercher user existant
        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            // Créer le user
            $user = new User();
            $user->setEmail($email);
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setGoogleId($googleId);
            $user->setRoles(['ROLE_USER']);
            $user->setPassword(null); // OAuth = pas de mot de passe

            $em->persist($user);
            $em->flush();
        }

        // Générer JWT FollowUp
        $jwt = $jwtManager->create($user);

        // Rediriger vers Angular avec le JWT dans l’URL
        return $this->redirect("http://localhost:4200/dashboard?token=$jwt");
    }

}
