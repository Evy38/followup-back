<?php

namespace App\Controller\Auth;

use App\Service\OAuthUserService;
use App\Service\GoogleAuthService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Cookie;

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
        OAuthUserService $oauthUserService,
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

        // Délégation à OAuthUserService pour la gestion utilisateur OAuth
        $user = $oauthUserService->getOrCreateFromGoogle($email, $firstName, $lastName, $googleId);

        error_log('[GOOGLE CALLBACK] User ID = ' . $user->getId());
        error_log('[GOOGLE CALLBACK] Email = ' . $user->getEmail());
        error_log('[GOOGLE CALLBACK] isVerified = ' . ($user->isVerified() ? 'true' : 'false'));

        // Générer JWT FollowUp
        $jwt = $jwtManager->create($user);
        error_log('[GOOGLE CALLBACK] JWT GENERATED = ' . substr($jwt, 0, 40) . '...');

        // ✅ Redirect vers ton composant Angular qui stocke en localStorage
        return $this->redirect('http://localhost:4200/google-callback?token=' . urlencode($jwt));


    }

}
