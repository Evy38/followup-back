<?php

namespace App\Tests\Api;

use App\Entity\User;
use App\Tests\DatabasePrimer;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthApiTest extends WebTestCase
{
    use DatabasePrimer;

    private function createVerifiedUser(string $email, string $plainPassword): User
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, $plainPassword));
        $user->setIsVerified(true);
        $user->setRoles(['ROLE_USER']);

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    public function test_login_with_valid_credentials_should_return_jwt_token(): void
    {
        $client = static::createClient();
        $user = $this->createVerifiedUser('test1_' . uniqid() . '@gmail.com', 'Password123');

        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $user->getEmail(),
            'password' => 'Password123',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Le body doit retourner authenticated: true (plus de token en clair)
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('authenticated', $responseData);
        $this->assertTrue($responseData['authenticated']);
        $this->assertArrayNotHasKey('token', $responseData);

        // Le cookie access_token HttpOnly doit être posé
        $cookies = $client->getCookieJar()->all();
        $accessTokenCookie = null;
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === 'access_token') {
                $accessTokenCookie = $cookie;
                break;
            }
        }
        $this->assertNotNull($accessTokenCookie, 'Le cookie access_token doit être présent');
        $this->assertTrue($accessTokenCookie->isHttpOnly(), 'Le cookie access_token doit être HttpOnly');

        // Le cookie doit contenir un JWT valide (3 parties séparées par des points)
        $tokenParts = explode('.', $accessTokenCookie->getValue());
        $this->assertCount(3, $tokenParts, 'Le cookie access_token doit contenir un JWT valide');

        // Le cookie refresh_token doit aussi être posé
        $refreshTokenCookie = null;
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === 'refresh_token') {
                $refreshTokenCookie = $cookie;
                break;
            }
        }
        $this->assertNotNull($refreshTokenCookie, 'Le cookie refresh_token doit être présent');
        $this->assertTrue($refreshTokenCookie->isHttpOnly(), 'Le cookie refresh_token doit être HttpOnly');
    }

    public function test_login_with_invalid_password_should_return_401_unauthorized(): void
    {
        $client = static::createClient();
        $user = $this->createVerifiedUser('test2_' . uniqid() . '@gmail.com', 'CorrectPassword123');

        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $user->getEmail(),
            'password' => 'WrongPassword456',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function test_login_with_non_existent_user_should_return_401_unauthorized(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'nonexistent_' . uniqid() . '@gmail.com',
            'password' => 'Password123',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function test_login_with_missing_credentials_should_return_400_bad_request(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['password' => 'Password123']));
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => 'test@gmail.com']));
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function test_me_with_valid_cookie_should_return_user(): void
    {
        $client = static::createClient();
        $user = $this->createVerifiedUser('test3_' . uniqid() . '@gmail.com', 'Password123');

        // Login pour récupérer le cookie
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $user->getEmail(),
            'password' => 'Password123',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Le cookie est automatiquement renvoyé par le client de test
        $client->request('GET', '/api/me');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['authenticated']);
    }

    public function test_me_without_cookie_should_return_401(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/me');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function test_me_with_authorization_header_should_return_401(): void
    {
        $client = static::createClient();
        $user = $this->createVerifiedUser('test4_' . uniqid() . '@gmail.com', 'Password123');

        // Générer un JWT manuellement via le service
        $jwtManager = static::getContainer()->get('lexik_jwt_authentication.jwt_manager');
        $token = $jwtManager->create($user);

        // Tenter d'utiliser le header Authorization (doit échouer — désactivé)
        $client->request('GET', '/api/me', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function test_logout_should_expire_cookies(): void
    {
        $client = static::createClient();
        $user = $this->createVerifiedUser('test5_' . uniqid() . '@gmail.com', 'Password123');

        // Login
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $user->getEmail(),
            'password' => 'Password123',
        ]));
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Logout
        $client->request('POST', '/api/logout');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Vérifier que les cookies sont expirés dans la réponse
        $setCookieHeaders = $client->getResponse()->headers->all('set-cookie');
        $this->assertNotEmpty($setCookieHeaders, 'Des cookies doivent être modifiés lors du logout');

        $cookieString = implode(' ', $setCookieHeaders);
        $this->assertStringContainsString('access_token', $cookieString);
        $this->assertStringContainsString('refresh_token', $cookieString);
    }
}
