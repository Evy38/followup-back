<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Entreprise;
use App\Entity\Ville;
use App\Entity\Statut;
use App\Entity\Candidature;
use App\Entity\Canal;
use App\Entity\MotCle;
use App\Entity\Reponse;
use App\Entity\Relance;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // === 1️⃣ Villes ===
        $villes = [];
        for ($i = 0; $i < 10; $i++) {
            $ville = new Ville();
            $ville->setNomVille($faker->city());
            $ville->setPays('France');
            $ville->setCodePostal($faker->postcode());

            $manager->persist($ville);
            $villes[] = $ville;
        }

        // === 2️⃣ Entreprises ===
        $entreprises = [];
        for ($i = 0; $i < 10; $i++) {
            $entreprise = new Entreprise();
            if (method_exists($entreprise, 'setNom')) {
                $entreprise->setNom($faker->company());
            }
            // set secteur and siteWeb if available in entity
            if (method_exists($entreprise, 'setSecteur')) {
                $entreprise->setSecteur($faker->jobTitle());
            }
            if (method_exists($entreprise, 'setSiteWeb')) {
                $entreprise->setSiteWeb($faker->url());
            }
            $manager->persist($entreprise);
            $entreprises[] = $entreprise;
        }

        // === 3️⃣ Utilisateurs ===
        $users = [];
        
        // Créer un utilisateur de test avec email/mot de passe connus
        $testUser = new User();
        $testUser->setEmail('test@example.com');
        $testUser->setPassword($this->passwordHasher->hashPassword($testUser, 'test1234'));
        $testUser->setRoles(['ROLE_USER']);
        $manager->persist($testUser);
        $users[] = $testUser;
        
        // Créer d'autres utilisateurs aléatoirement
        for ($i = 0; $i < 4; $i++) {
            $user = new User();
            $user->setEmail($faker->unique()->safeEmail());
            $user->setPassword($this->passwordHasher->hashPassword($user, 'test1234'));
            $user->setRoles(['ROLE_USER']);
            $manager->persist($user);
            $users[] = $user;
        }

        // === 4️⃣ Statuts ===
        $statuts = [];
        $nomsStatuts = ['En attente', 'Entretien', 'Refusé', 'Accepté'];
        foreach ($nomsStatuts as $nom) {
            $statut = new Statut();
            // Statut entity uses setLibelle()
            if (method_exists($statut, 'setLibelle')) {
                $statut->setLibelle($nom);
            }
            $manager->persist($statut);
            $statuts[] = $statut;
        }

        // === 5️⃣ Canaux ===
        $canaux = [];
        foreach (['LinkedIn', 'Indeed', 'Pôle Emploi', 'Candidature spontanée'] as $nom) {
            $canal = new Canal();
            // Canal entity uses setLibelle()
            if (method_exists($canal, 'setLibelle')) {
                $canal->setLibelle($nom);
            }
            $manager->persist($canal);
            $canaux[] = $canal;
        }

        // === 6️⃣ Mots-clés ===
        $motsCles = [];
        foreach (['Symfony', 'Angular', 'Docker', 'DevOps', 'React'] as $mot) {
            $motCle = new MotCle();
            if (method_exists($motCle, 'setLibelle')) {
                $motCle->setLibelle($mot);
            }
            $manager->persist($motCle);
            $motsCles[] = $motCle;
        }

        // === 7️⃣ Candidatures ===
        $candidatures = [];
        for ($i = 0; $i < 20; $i++) {
            $candidature = new Candidature();
            // setDateCandidature expects \DateTime (mutable)
            if (method_exists($candidature, 'setDateCandidature')) {
                $candidature->setDateCandidature($faker->dateTimeBetween('-2 months', 'now'));
            }
            if (method_exists($candidature, 'setStatut')) {
                $candidature->setStatut($faker->randomElement($statuts));
            }
            if (method_exists($candidature, 'setCanal')) {
                $candidature->setCanal($faker->randomElement($canaux));
            }
            if (method_exists($candidature, 'setEntreprise')) {
                $candidature->setEntreprise($faker->randomElement($entreprises));
            }
            if (method_exists($candidature, 'setUser')) {
                $candidature->setUser($faker->randomElement($users));
            }

            $manager->persist($candidature);
            $candidatures[] = $candidature;
        }

        // === 8️⃣ Réponses ===
        $reponses = [];
        for ($i = 0; $i < 10; $i++) {
            $reponse = new Reponse();
            // Reponse entity uses setCommentaire() for the text
            if (method_exists($reponse, 'setCommentaire')) {
                $reponse->setCommentaire($faker->paragraph());
            }
            if (method_exists($reponse, 'setDateReponse')) {
                $reponse->setDateReponse(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-1 month', 'now')));
            }
            if (method_exists($reponse, 'setCandidature')) {
                $reponse->setCandidature($faker->randomElement($candidatures));
            }
            $manager->persist($reponse);
            $reponses[] = $reponse;
        }

        // === 9️⃣ Relances ===
        for ($i = 0; $i < 10; $i++) {
            $relance = new Relance();
            if (method_exists($relance, 'setDateRelance')) {
                $relance->setDateRelance(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-15 days', 'now')));
            }
            if (method_exists($relance, 'setCandidature')) {
                $relance->setCandidature($faker->randomElement($candidatures));
            }
            // Relance entity does not have a Canal relation; use type or contenu instead
            if (method_exists($relance, 'setType')) {
                $relance->setType($faker->randomElement(['email', 'appel', 'linkedin']));
            }
            $manager->persist($relance);
        }

        $manager->flush();
    }
}
