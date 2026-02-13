<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use App\Service\EmailVerificationService;
use App\Service\SecurityEmailService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * 🎓 TESTS UNITAIRES pour UserService
 * 
 */
class UserServiceTest extends TestCase
{
    /**
     * 🧪 TEST #1 : Vérifier que le mot de passe est bien hashé à la création
     * 
     * 🎯 Objectif : S'assurer que UserService::create() hash bien le mot de passe
     */
    public function test_create_should_hash_password(): void
    {
        // ========================================
        // ARRANGE : Préparer les mocks
        // ========================================
        
        // 🔹 Mock du Repository : On simule qu'aucun user n'existe avec cet email
        $repository = $this->createMock(UserRepository::class);
        $repository->method('existsByEmail')->willReturn(false); // L'email est disponible
        $repository->method('save'); // Simule la sauvegarde (void return)
        
        // 🔹 Mock du Hasher : On simule le hashage du mot de passe
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hashed_password_123'); // Simule le hash
        
        // 🔹 Mock de l'EntityManager (requis par le constructeur)
        $em = $this->createMock(EntityManagerInterface::class);
        
        // 🔹 Mock du EmailVerificationService : On simule la génération du token
        $emailService = $this->createMock(EmailVerificationService::class);
        $emailService->method('generateVerificationToken'); // void return
        $emailService->method('sendVerificationEmail'); // void return
        
        // 🔹 Mock du SecurityEmailService (requis par le constructeur)
        $securityEmailService = $this->createMock(SecurityEmailService::class);
        
        // 🔹 Créer le UserService avec tous les mocks
        $userService = new UserService(
            $repository,
            $hasher,
            $em,
            $emailService,
            $securityEmailService
        );
        
        // 🔹 Créer un User avec un mot de passe en clair
        $user = new User();
        $user->setEmail('test@gmail.com');
        $user->setPassword('PlainPassword123'); // Mot de passe en clair
        
        // ========================================
        //  ACT : Exécuter l'action à tester
        // ========================================
        $result = $userService->create($user);
        
        // ========================================
        //  ASSERT : Vérifier le résultat
        // ========================================
        
        // Vérification : Le mot de passe doit être hashé (pas en clair)
        $this->assertEquals('hashed_password_123', $result->getPassword());
        
        // Vérification : Le mot de passe ne doit PLUS être en clair
        $this->assertNotEquals('PlainPassword123', $result->getPassword());
    }
    
    /**
     * 🧪 TEST #2 : Vérifier qu'une exception est levée si l'email existe déjà
     * 
     * 🎯 Objectif : S'assurer que UserService::create() empêche les doublons
     */
    public function test_create_should_throw_exception_if_email_exists(): void
    {
        // ========================================
        // ARRANGE
        // ========================================
        
        // 🔹 Mock du Repository : On simule qu'un user existe DÉJÀ avec cet email
        $repository = $this->createMock(UserRepository::class);
        $repository->method('existsByEmail')->willReturn(true); // ❌ Email déjà pris !
        
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $emailService = $this->createMock(EmailVerificationService::class);
        $securityEmailService = $this->createMock(SecurityEmailService::class);
        
        $userService = new UserService(
            $repository,
            $hasher,
            $em,
            $emailService,
            $securityEmailService
        );
        
        $user = new User();
        $user->setEmail('existing@gmail.com');
        $user->setPassword('Password123');
        
        // ========================================
        //  ACT + ASSERT
        // ========================================
        
        // On s'attend à ce qu'une exception ConflictHttpException soit levée
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage("Cet email est déjà utilisé.");
        
        // Cette ligne doit déclencher l'exception
        $userService->create($user);
    }
    
    /**
     * 🧪 TEST #3 : Vérifier qu'une exception est levée si l'email n'est pas Gmail
     * 
     * 🎯 Objectif : S'assurer que UserService::create() respecte la règle métier
     */
    public function test_create_should_throw_exception_if_email_not_gmail(): void
    {
        // ========================================
        // ARRANGE
        // ========================================
        
        $repository = $this->createMock(UserRepository::class);
        $repository->method('existsByEmail')->willReturn(false); // Email disponible
        
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $emailService = $this->createMock(EmailVerificationService::class);
        $securityEmailService = $this->createMock(SecurityEmailService::class);
        
        $userService = new UserService(
            $repository,
            $hasher,
            $em,
            $emailService,
            $securityEmailService
        );
        
        // 🔹 Créer un user avec un email NON Gmail
        $user = new User();
        $user->setEmail('test@yahoo.com'); // ❌ Pas un Gmail !
        $user->setPassword('Password123');
        
        // ========================================
        // ACT + ASSERT
        // ========================================
        
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage("Pour FollowUp, l'email doit être une adresse Gmail.");
        
        $userService->create($user);
    }
    
    /**
     * 🧪 TEST #4 : Vérifier qu'un token de vérification est généré
     * 
     * 🎯 Objectif : S'assurer que EmailVerificationService::generateVerificationToken() 
     *              est bien appelé lors de la création d'un user
     */
    public function test_create_should_generate_verification_token(): void
    {
        // ========================================
        // ARRANGE
        // ========================================
        
        $repository = $this->createMock(UserRepository::class);
        $repository->method('existsByEmail')->willReturn(false);
        $repository->method('save'); // void return
        
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hashed_password_123');
        
        $em = $this->createMock(EntityManagerInterface::class);
        
        // 🔹 Mock spécial : On veut VÉRIFIER que generateVerificationToken() est appelé
        $emailService = $this->createMock(EmailVerificationService::class);
        $emailService->expects($this->once()) // Doit être appelé EXACTEMENT 1 fois
                     ->method('generateVerificationToken')
                     ->with($this->isInstanceOf(User::class)); // Avec un objet User
        
        $emailService->method('sendVerificationEmail'); // void return
        
        $securityEmailService = $this->createMock(SecurityEmailService::class);
        
        $userService = new UserService(
            $repository,
            $hasher,
            $em,
            $emailService,
            $securityEmailService
        );
        
        $user = new User();
        $user->setEmail('test@gmail.com');
        $user->setPassword('Password123');
        
        // ========================================
        // ACT
        // ========================================
        $userService->create($user);
        
        // ========================================
        // ASSERT
        // ========================================
        
        // La vérification se fait automatiquement via expects($this->once())
        // Si generateVerificationToken() n'est PAS appelé, le test échouera !
    }
    
    /**
     * 🧪 TEST #5 : Vérifier qu'une exception est levée si l'utilisateur est introuvable
     * 
     * 🎯 Objectif : S'assurer que UserService::getById() lève une exception NotFoundHttpException
     *              quand l'ID n'existe pas
     */
    public function test_getById_should_throw_exception_if_user_not_found(): void
    {
        // ========================================
        // ARRANGE
        // ========================================
        
        // 🔹 Mock du Repository : On simule que find() retourne null (user introuvable)
        $repository = $this->createMock(UserRepository::class);
        $repository->method('find')->willReturn(null); // ❌ Aucun user trouvé
        
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $emailService = $this->createMock(EmailVerificationService::class);
        $securityEmailService = $this->createMock(SecurityEmailService::class);
        
        $userService = new UserService(
            $repository,
            $hasher,
            $em,
            $emailService,
            $securityEmailService
        );
        
        // ========================================
        // ACT + ASSERT
        // ========================================
        
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage("Utilisateur #999 introuvable.");
        
        $userService->getById(999); // Chercher un ID qui n'existe pas
    }
}