<?php

namespace App\Security;

use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationSuccessResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private EventDispatcherInterface $dispatcher,
        private RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private RefreshTokenManagerInterface $refreshTokenManager,
        private bool $cookieSecure,
        private int $refreshTokenTtl = 604800,
    ) {}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();
        $jwt = $this->jwtManager->create($user);

        $event = new AuthenticationSuccessEvent(['token' => $jwt], $user, new JWTAuthenticationSuccessResponse($jwt));
        $this->dispatcher->dispatch($event, Events::AUTHENTICATION_SUCCESS);

        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, $this->refreshTokenTtl);
        $this->refreshTokenManager->save($refreshToken);

        $response = new JWTAuthenticationSuccessResponse($jwt);
        $response->setData(['authenticated' => true]);

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
