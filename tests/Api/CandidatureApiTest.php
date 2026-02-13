<?php

namespace App\Tests\Api;

use App\Entity\Candidature;
use App\Entity\Entreprise;
use App\Entity\User;
use App\Entity\Statut;
use App\Enum\StatutReponse;
use App\Tests\DatabasePrimer;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CandidatureApiTest extends WebTestCase
{
    use DatabasePrimer;

    public function test_authenticated_user_can_get_their_candidatures(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $jwtManager = static::getContainer()->get(JWTTokenManagerInterface::class);

        // =========================
        // Création du Statut (obligatoire)
        // =========================
        $statut = new Statut();
        $statut->setLibelle('En cours');
        $entityManager->persist($statut);

        // =========================
        // User
        // =========================
        $user = new User();
        $user->setEmail('test1_' . uniqid() . '@gmail.com');
        $user->setPassword($hasher->hashPassword($user, 'Password123'));
        $user->setIsVerified(true);
        $user->setRoles(['ROLE_USER']);
        $entityManager->persist($user);

        // =========================
        // Entreprises
        // =========================
        $entreprise1 = new Entreprise();
        $entreprise1->setNom('Entreprise A');
        $entityManager->persist($entreprise1);

        $entreprise2 = new Entreprise();
        $entreprise2->setNom('Entreprise B');
        $entityManager->persist($entreprise2);

        // =========================
        // Candidatures
        // =========================
        $candidature1 = new Candidature();
        $candidature1->setUser($user);
        $candidature1->setEntreprise($entreprise1);
        $candidature1->setStatut($statut);
        $candidature1->setJobTitle('Développeur PHP');
        $candidature1->setExternalOfferId('offer_' . uniqid());
        $candidature1->setDateCandidature(new \DateTimeImmutable('2025-01-15'));
        $candidature1->setStatutReponse(StatutReponse::ATTENTE);

        $candidature2 = new Candidature();
        $candidature2->setUser($user);
        $candidature2->setEntreprise($entreprise2);
        $candidature2->setStatut($statut);
        $candidature2->setJobTitle('Lead Developer');
        $candidature2->setExternalOfferId('offer_' . uniqid());
        $candidature2->setDateCandidature(new \DateTimeImmutable('2025-01-20'));
        $candidature2->setStatutReponse(StatutReponse::ENTRETIEN);

        $entityManager->persist($candidature1);
        $entityManager->persist($candidature2);
        $entityManager->flush();

        $token = $jwtManager->create($user);

        $client->request('GET', '/api/my-candidatures', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(2, $responseData);
    }

    public function test_unauthenticated_user_cannot_access_candidatures(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/my-candidatures');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function test_user_with_invalid_token_cannot_access_candidatures(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/my-candidatures', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer invalid.token'
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function test_user_can_only_see_their_own_candidatures(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $jwtManager = static::getContainer()->get(JWTTokenManagerInterface::class);

        $statut = new Statut();
        $statut->setLibelle('En cours');
        $entityManager->persist($statut);

        // USER A
        $userA = new User();
        $userA->setEmail('usera_' . uniqid() . '@gmail.com');
        $userA->setPassword($hasher->hashPassword($userA, 'Password123'));
        $userA->setIsVerified(true);
        $userA->setRoles(['ROLE_USER']);
        $entityManager->persist($userA);

        $entrepriseA = new Entreprise();
        $entrepriseA->setNom('Entreprise A');
        $entityManager->persist($entrepriseA);

        $candidatureA = new Candidature();
        $candidatureA->setUser($userA);
        $candidatureA->setEntreprise($entrepriseA);
        $candidatureA->setStatut($statut);
        $candidatureA->setJobTitle('Candidature de A');
        $candidatureA->setExternalOfferId('offer_' . uniqid());
        $candidatureA->setDateCandidature(new \DateTimeImmutable());
        $candidatureA->setStatutReponse(StatutReponse::ATTENTE);

        $entityManager->persist($candidatureA);

        // USER B
        $userB = new User();
        $userB->setEmail('userb_' . uniqid() . '@gmail.com');
        $userB->setPassword($hasher->hashPassword($userB, 'Password123'));
        $userB->setIsVerified(true);
        $userB->setRoles(['ROLE_USER']);
        $entityManager->persist($userB);

        $entrepriseB = new Entreprise();
        $entrepriseB->setNom('Entreprise B');
        $entityManager->persist($entrepriseB);

        $candidatureB = new Candidature();
        $candidatureB->setUser($userB);
        $candidatureB->setEntreprise($entrepriseB);
        $candidatureB->setStatut($statut);
        $candidatureB->setJobTitle('Candidature de B');
        $candidatureB->setExternalOfferId('offer_' . uniqid());
        $candidatureB->setDateCandidature(new \DateTimeImmutable());
        $candidatureB->setStatutReponse(StatutReponse::ATTENTE);

        $entityManager->persist($candidatureB);
        $entityManager->flush();

        $tokenA = $jwtManager->create($userA);

        $client->request('GET', '/api/my-candidatures', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $tokenA
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $responseData);
    }
}
