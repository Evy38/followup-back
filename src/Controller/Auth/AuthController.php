<?php

namespace App\Controller\Auth;

use App\Service\OAuthUserService;
use App\Service\GoogleAuthService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Gère l'authentification OAuth 2.0 via Google.
 *
 * Endpoints :
 * - GET /auth/google           Redirige l'utilisateur vers la page de consentement Google
 * - GET /google/callback        Reçoit le code d'autorisation, échange contre un JWT FollowUp
 *
 * Flux OAuth :
 * 1. Frontend appelle `/auth/google` → redirection vers Google
 * 2. Google redirige sur `/google/callback?code=...`
 * 3. Le callback échange le code → récupère le profil Google → crée/récupère le User
 * 4. Un JWT FollowUp est généré et transmis au frontend via une redirection
 */
class AuthController extends AbstractController
{
    /**
     * Initie le flux OAuth Google en redirigeant l'utilisateur vers la page de consentement.
     *
     * GET /auth/google
     */
    #[Route('/auth/google', name: 'auth_google')]
    public function google(GoogleAuthService $googleAuthService): RedirectResponse
    {
        $client = $googleAuthService->getClient();
        $authUrl = $client->createAuthUrl();

        return $this->redirect($authUrl);
    }

    /**
     * Reçoit le callback Google OAuth, crée ou met à jour le compte utilisateur,
     * génère un JWT FollowUp et redirige le frontend avec le token dans l'URL.
     *
     * GET /google/callback?code=...
     *
     * En cas d'erreur (code manquant, token invalide, compte supprimé),
     * redirige vers `/login?error={raison}`.
     */
    #[Route('/google/callback', name: 'auth_google_callback')]
    public function googleCallback(
        Request $request,
        GoogleAuthService $googleAuthService,
        OAuthUserService $oauthUserService,
        JWTTokenManagerInterface $jwtManager
    ): RedirectResponse {
        $frontendUrl = $this->getParameter('frontend_url');

        $client = $googleAuthService->getClient();
        $code = $request->query->get('code');

        if (!$code) {
            return $this->redirect($frontendUrl . '/login?error=no_code');
        }
        error_log("code:" . $code);
        error_log("Google callback: code received, fetching access token...");
        $token = $client->fetchAccessTokenWithAuthCode($code);
        error_log(print_r($token, 1));

        if (isset($token['error'])) {
            error_log("Google token error: " . json_encode($token));
            return $this->redirect($frontendUrl . '/login?error=token');
        }

        error_log("Google token received successfully");

        $oauth = new \Google\Service\Oauth2($client);
        $googleUser = $oauth->userinfo->get();

        $email = $googleUser->email;
        $firstName = $googleUser->givenName;
        $lastName = $googleUser->familyName;
        $googleId = $googleUser->id;

        try {
            $user = $oauthUserService->getOrCreateFromGoogle($email, $firstName, $lastName, $googleId);
        } catch (\RuntimeException $e) {
            return $this->redirect($frontendUrl . '/login?error=account_deleted');
        }

        $jwt = $jwtManager->create($user);

        return $this->redirect($frontendUrl . '/google/callback?token=' . urlencode($jwt));
    }
}