<?php

namespace App\Controller\Auth;

use App\Service\OAuthUserService;
use App\Service\GoogleAuthService;
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
        OAuthUserService $oauthUserService,
        JWTTokenManagerInterface $jwtManager,
        string $frontendUrl
    ): RedirectResponse {

        $client = $googleAuthService->getClient();
        $code = $request->query->get('code');

        if (!$code) {
            return $this->redirect($frontendUrl . '/login?error=no_code');
        }

        // Récupération du token Google
        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            return $this->redirect($frontendUrl . '/login?error=token');

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

        // Générer JWT FollowUp
        $jwt = $jwtManager->create($user);

        // ✅ Redirect vers ton composant Angular qui stocke en localStorage
        return $this->redirect($frontendUrl . '/google-callback?token=' . urlencode($jwt));


    }

}
