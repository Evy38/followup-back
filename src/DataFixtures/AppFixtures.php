<?php

namespace App\DataFixtures;

use App\Entity\Candidature;
use App\Entity\Entreprise;
use App\Entity\Entretien;
use App\Entity\Relance;
use App\Entity\User;
use App\Entity\Statut;
use App\Enum\ResultatEntretien;
use App\Enum\StatutEntretien;
use App\Enum\StatutReponse;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Jeu de données de démonstration pour l'environnement de développement.
 *
 * Chargement : `php bin/console doctrine:fixtures:load`
 *
 * Données générées :
 * - 3 utilisateurs : admin (ROLE_ADMIN), julien.dev@gmail.com (ROLE_USER actif),
 *   marie.test@gmail.com (email non vérifié)
 * - 5 entreprises (Accenture, Capgemini, Sopra Steria, Thales, Orange)
 * - 6 statuts de candidature (Envoyée, En cours, Relancée, Entretien, Refusée, Acceptée)
 * - 15 candidatures avec des statuts de réponse variés pour tester tous les cas UI
 * - 8 entretiens (prévus, passés avec résultats positifs/négatifs/en attente)
 * - Des relances pour 2 candidatures anciennes
 *
 * Mots de passe des comptes de test : `Admin123!` / `User123!`
 */
class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $users = $this->createUsers($manager);
        $entreprises = $this->createEntreprises($manager);
        $statuts = $this->createStatuts($manager);
        $candidatures = $this->createCandidatures($manager, $users, $entreprises, $statuts);

        $this->createEntretiens($manager, $candidatures);
        $this->createRelances($manager, $candidatures);

        $manager->flush();

        echo "Fixtures chargées avec succès !\n";
        echo "📊 Utilisateurs : " . count($users) . "\n";
        echo "📊 Entreprises : " . count($entreprises) . "\n";
        echo "📊 Candidatures : " . count($candidatures) . "\n";
    }

    /* ========================= USERS ========================= */

    private function createUsers(ObjectManager $manager): array
    {
        $users = [];

        // ===== ADMIN =====
        $admin = (new User())
            ->setEmail('admin@followup.com')
            ->setPassword(
                $this->passwordHasher->hashPassword(new User(), 'Admin123!')
            )
            ->setFirstName('Admin')
            ->setLastName('FollowUp')
            ->setRoles(['ROLE_ADMIN', 'ROLE_USER'])
            ->setIsVerified(true)
            ->setConsentRgpd(true)
            ->setConsentRgpdAt(new \DateTimeImmutable());

        $manager->persist($admin);
        $users[] = $admin;

        // ===== USER ACTIF =====
        $user = (new User())
            ->setEmail('julien.dev@gmail.com')
            ->setPassword(
                $this->passwordHasher->hashPassword(new User(), 'User123!')
            )
            ->setFirstName('Julien')
            ->setLastName('Dupont')
            ->setRoles(['ROLE_USER'])
            ->setIsVerified(true)
            ->setConsentRgpd(true)
            ->setConsentRgpdAt(new \DateTimeImmutable());

        $manager->persist($user);
        $users[] = $user;

        // ===== USER EN ATTENTE DE VALIDATION EMAIL =====
        $newUser = (new User())
            ->setEmail('marie.test@gmail.com')
            ->setPassword(
                $this->passwordHasher->hashPassword(new User(), 'User123!')
            )
            ->setFirstName('Marie')
            ->setLastName('Martin')
            ->setRoles(['ROLE_USER'])
            ->setIsVerified(false)
            ->setEmailVerificationToken(bin2hex(random_bytes(32)))
            ->setEmailVerificationTokenExpiresAt(
                new \DateTimeImmutable('+24 hours')
            )
            ->setConsentRgpd(true)
            ->setConsentRgpdAt(new \DateTimeImmutable());

        $manager->persist($newUser);
        $users[] = $newUser;

        return $users;
    }


    /* ========================= ENTREPRISES ========================= */

    private function createEntreprises(ObjectManager $manager): array
    {
        $noms = [
            'Accenture France',
            'Capgemini',
            'Sopra Steria',
            'Thales Group',
            'Orange Business Services',
        ];

        $entreprises = [];

        foreach ($noms as $nom) {
            $entreprise = new Entreprise();
            $entreprise->setNom($nom);
            $manager->persist($entreprise);
            $entreprises[] = $entreprise;
        }

        return $entreprises;
    }

    /* ========================= STATUTS ========================= */

    private function createStatuts(ObjectManager $manager): array
    {
        $libelles = [
            'Envoyée',
            'En cours',
            'Relancée',
            'Entretien',
            'Refusée',
            'Acceptée',
        ];

        $statuts = [];

        foreach ($libelles as $libelle) {
            $statut = new Statut();
            $statut->setLibelle($libelle);

            $manager->persist($statut);
            $statuts[] = $statut;
        }

        return $statuts;
    }

    /* ========================= CANDIDATURES ========================= */

    /**
     * Crée 15 candidatures avec des statuts variés.
     * 
     * @return Candidature[] Tableau des candidatures créées
     */
    private function createCandidatures(
        ObjectManager $manager,
        array $users,
        array $entreprises,
        array $statuts
    ): array {
        $candidatures = [];
        $user = $users[1]; // Julien Dupont

        // Données réalistes de candidatures
        $candidaturesData = [
            // Candidatures en attente (récentes)
            [
                'entreprise' => $entreprises[0],
                'poste' => 'Développeur Full Stack',
                'statut' => StatutReponse::ATTENTE, // Enum PHP natif
                'date' => '-3 days',
                'lien' => 'https://careers.accenture.com/fr-fr/job-123',
            ],
            [
                'entreprise' => $entreprises[1],
                'poste' => 'Concepteur Développeur Angular',
                'statut' => StatutReponse::ATTENTE,
                'date' => '-5 days',
                'lien' => 'https://capgemini.com/careers/job-456',
            ],

            // Candidatures avec échanges
            [
                'entreprise' => $entreprises[2],
                'poste' => 'Lead Developer PHP Symfony',
                'statut' => StatutReponse::ECHANGES,
                'date' => '-10 days',
                'lien' => 'https://soprasteria.com/jobs/789',
            ],
            [
                'entreprise' => $entreprises[3],
                'poste' => 'Ingénieur Logiciel Backend',
                'statut' => StatutReponse::ECHANGES,
                'date' => '-12 days',
                'lien' => 'https://thalesgroup.com/careers/job-321',
            ],

            // Candidatures avec entretiens prévus
            [
                'entreprise' => $entreprises[4],
                'poste' => 'Développeur API REST',
                'statut' => StatutReponse::ENTRETIEN,
                'date' => '-15 days',
                'lien' => 'https://orange-business.com/job-654',
            ],
            [
                'entreprise' => $entreprises[0],
                'poste' => 'Tech Lead',
                'statut' => StatutReponse::ENTRETIEN,
                'date' => '-18 days',
                'lien' => 'https://careers.accenture.com/fr-fr/job-987',
            ],

            // Candidatures engagées (succès) 🎉
            [
                'entreprise' => $entreprises[1],
                'poste' => 'Développeur Senior Symfony',
                'statut' => StatutReponse::ENGAGE,
                'date' => '-30 days',
                'lien' => 'https://capgemini.com/careers/job-111',
            ],

            // Candidatures négatives (refus)
            [
                'entreprise' => $entreprises[2],
                'poste' => 'Développeur JavaScript',
                'statut' => StatutReponse::NEGATIVE,
                'date' => '-25 days',
                'lien' => 'https://soprasteria.com/jobs/222',
            ],
            [
                'entreprise' => $entreprises[3],
                'poste' => 'Développeur Mobile Flutter',
                'statut' => StatutReponse::NEGATIVE,
                'date' => '-28 days',
                'lien' => 'https://thalesgroup.com/careers/job-333',
            ],

            // Candidatures annulées
            [
                'entreprise' => $entreprises[4],
                'poste' => 'DevOps Engineer',
                'statut' => StatutReponse::ANNULE,
                'date' => '-20 days',
                'lien' => 'https://orange-business.com/job-444',
            ],

            // Candidatures anciennes en attente (relances nécessaires)
            [
                'entreprise' => $entreprises[0],
                'poste' => 'Architecte Logiciel',
                'statut' => StatutReponse::ATTENTE,
                'date' => '-22 days',
                'lien' => 'https://careers.accenture.com/fr-fr/job-555',
            ],
            [
                'entreprise' => $entreprises[1],
                'poste' => 'Scrum Master',
                'statut' => StatutReponse::ATTENTE,
                'date' => '-24 days',
                'lien' => 'https://capgemini.com/careers/job-666',
            ],

            // Mix de statuts pour avoir de la diversité
            [
                'entreprise' => $entreprises[2],
                'poste' => 'Product Owner',
                'statut' => StatutReponse::ECHANGES,
                'date' => '-8 days',
                'lien' => 'https://soprasteria.com/jobs/777',
            ],
            [
                'entreprise' => $entreprises[3],
                'poste' => 'Data Engineer',
                'statut' => StatutReponse::ATTENTE,
                'date' => '-6 days',
                'lien' => 'https://thalesgroup.com/careers/job-888',
            ],
            [
                'entreprise' => $entreprises[4],
                'poste' => 'Cloud Architect',
                'statut' => StatutReponse::NEGATIVE,
                'date' => '-35 days',
                'lien' => 'https://orange-business.com/job-999',
            ],
        ];

        foreach ($candidaturesData as $data) {
            $candidature = new Candidature();
            $candidature->setUser($user);
            $candidature->setEntreprise($data['entreprise']);
            $candidature->setStatut($statuts[array_rand($statuts)]);
            $candidature->setJobTitle($data['poste']);
            $candidature->setExternalOfferId(
                'FIXTURE-' . strtoupper(bin2hex(random_bytes(6)))
            );
            $candidature->setStatutReponse($data['statut']);
            $candidature->setDateCandidature(new \DateTimeImmutable($data['date']));
            $candidature->setLienAnnonce($data['lien']);

            $manager->persist($candidature);
            $candidatures[] = $candidature;
        }

        return $candidatures;
    }

    /* ========================= ENTRETIENS ========================= */

    private function createEntretiens(ObjectManager $manager, array $candidatures): void
    {
        //  Utiliser DateTime au lieu de DateTimeImmutable
        // pour les types 'date' et 'time' de Doctrine

        // Entretien prévu (futur)
        $entretienPrevu = new Entretien();
        $entretienPrevu->setCandidature($candidatures[4]); // "Développeur API REST"
        $entretienPrevu->setStatut(StatutEntretien::PREVU); // Enum correctement typé
        $entretienPrevu->setDateEntretien(new \DateTime('+3 days'));
        $entretienPrevu->setHeureEntretien(new \DateTime('10:00'));
        $manager->persist($entretienPrevu);

        // Entretien prévu proche
        $entretienPrevu2 = new Entretien();
        $entretienPrevu2->setCandidature($candidatures[5]); // "Tech Lead"
        $entretienPrevu2->setStatut(StatutEntretien::PREVU);
        $entretienPrevu2->setDateEntretien(new \DateTime('+1 week'));
        $entretienPrevu2->setHeureEntretien(new \DateTime('13:30'));
        $manager->persist($entretienPrevu2);

        // Entretien passé - résultat positif (engagé)
        $entretienEngage = new Entretien();
        $entretienEngage->setCandidature($candidatures[6]); // "Développeur Senior Symfony"
        $entretienEngage->setStatut(StatutEntretien::PASSE); // Enum
        $entretienEngage->setResultat(ResultatEntretien::ENGAGE); // Enum
        $entretienEngage->setDateEntretien(new \DateTime('-25 days'));
        $entretienEngage->setHeureEntretien(new \DateTime('09:00'));
        $manager->persist($entretienEngage);

        // Entretien passé - résultat négatif
        $entretienNegatif = new Entretien();
        $entretienNegatif->setCandidature($candidatures[7]); // "Développeur JavaScript"
        $entretienNegatif->setStatut(StatutEntretien::PASSE);
        $entretienNegatif->setResultat(ResultatEntretien::NEGATIVE);
        $entretienNegatif->setDateEntretien(new \DateTime('-20 days'));
        $entretienNegatif->setHeureEntretien(new \DateTime('15:00'));
        $manager->persist($entretienNegatif);

        // Entretien passé - en attente de réponse
        $entretienAttente = new Entretien();
        $entretienAttente->setCandidature($candidatures[12]); // "Product Owner"
        $entretienAttente->setStatut(StatutEntretien::PASSE);
        $entretienAttente->setResultat(ResultatEntretien::ATTENTE);
        $entretienAttente->setDateEntretien(new \DateTime('-5 days'));
        $entretienAttente->setHeureEntretien(new \DateTime('16:30'));
        $manager->persist($entretienAttente);

        // Entretien passé - négatif (autre candidature)
        $entretienNegatif2 = new Entretien();
        $entretienNegatif2->setCandidature($candidatures[8]); // "Développeur Mobile Flutter"
        $entretienNegatif2->setStatut(StatutEntretien::PASSE);
        $entretienNegatif2->setResultat(ResultatEntretien::NEGATIVE);
        $entretienNegatif2->setDateEntretien(new \DateTime('-23 days'));
        $entretienNegatif2->setHeureEntretien(new \DateTime('11:30'));
        $manager->persist($entretienNegatif2);

        // Entretien passé - négatif (dernière candidature)
        $entretienNegatif3 = new Entretien();
        $entretienNegatif3->setCandidature($candidatures[14]); // "Cloud Architect"
        $entretienNegatif3->setStatut(StatutEntretien::PASSE);
        $entretienNegatif3->setResultat(ResultatEntretien::NEGATIVE);
        $entretienNegatif3->setDateEntretien(new \DateTime('-30 days'));
        $entretienNegatif3->setHeureEntretien(new \DateTime('14:15'));
        $manager->persist($entretienNegatif3);

        // Second entretien pour la candidature "engagé" (montrer le workflow complet)
        $entretien2Engage = new Entretien();
        $entretien2Engage->setCandidature($candidatures[6]); // "Développeur Senior Symfony"
        $entretien2Engage->setStatut(StatutEntretien::PASSE);
        $entretien2Engage->setResultat(ResultatEntretien::ENGAGE);
        $entretien2Engage->setDateEntretien(new \DateTime('-26 days'));
        $entretien2Engage->setHeureEntretien(new \DateTime('09:30'));
        $manager->persist($entretien2Engage);
    }

    /* ========================= RELANCES ========================= */

    private function createRelances(ObjectManager $manager, array $candidatures): void
    {
        $targets = [$candidatures[10], $candidatures[11]];

        foreach ($targets as $candidature) {
            foreach ([7, 14, 21] as $i => $days) {
                $r = new Relance();
                $r->setCandidature($candidature);
                $r->setRang($i + 1);
                $r->setDateRelance(
                    $candidature->getDateCandidature()->modify("+{$days} days")
                );
                $manager->persist($r);
            }
        }
    }
}