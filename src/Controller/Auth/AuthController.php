<?php

namespace App\Controller\Auth;

use App\Service\OAuthUserService;
use App\Service\GoogleAuthService;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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
 * 4. Les cookies HttpOnly access_token + refresh_token sont posés, puis redirection vers le frontend
 */
class AuthController extends AbstractController
{
    public function __construct(
        private bool $cookieSecure,
        private RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private RefreshTokenManagerInterface $refreshTokenManager,
    ) {}

    /**
     * GET /auth/google
     */
    #[Route('/auth/google', name: 'auth_google')]
    public function google(GoogleAuthService $googleAuthService): RedirectResponse
    {
        $authUrl = $googleAuthService->getClient()->createAuthUrl();
        return $this->redirect($authUrl);
    }

    /**
     * GET /google/callback?code=...
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

        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            return $this->redirect($frontendUrl . '/login?error=token');
        }

        $oauth = new \Google\Service\Oauth2($client);
        $googleUser = $oauth->userinfo->get();

        try {
            $user = $oauthUserService->getOrCreateFromGoogle(
                $googleUser->email,
                $googleUser->givenName,
                $googleUser->familyName,
                $googleUser->id
            );
        } catch (\RuntimeException $e) {
            return $this->redirect($frontendUrl . '/google/callback?error=account_deleted');
        }

        $jwt = $jwtManager->create($user);

        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, 604800);
        $this->refreshTokenManager->save($refreshToken);

        $response = new RedirectResponse($frontendUrl . '/google/callback');

        $sameSite = $this->cookieSecure ? Cookie::SAMESITE_NONE : Cookie::SAMESITE_LAX;

        $response->headers->setCookie(Cookie::create('access_token')
            ->withValue($jwt)
            ->withHttpOnly(true)
            ->withSecure($this->cookieSecure)
            ->withSameSite($sameSite)
            ->withPath('/')
        );

        $response->headers->setCookie(Cookie::create('refresh_token')
            ->withValue($refreshToken->getRefreshToken())
            ->withHttpOnly(true)
            ->withSecure($this->cookieSecure)
            ->withSameSite($sameSite)
            ->withPath('/')
            ->withExpires(new \DateTimeImmutable('+7 days'))
        );

        return $response;
    }
}
