<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\LoginAttemptHandler;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager,
        private UserRepository $userRepository,
        private LoginAttemptHandler $loginAttemptHandler
    ) {}

    //  ROUTE D'INSCRIPTION - DÉSACTIVÉE (Utiliser UserController pour créer des utilisateurs)
    #[Route('/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        // SÉCURITÉ: L'inscription publique est désactivée pour éviter l'escalade de privilèges
        // Seuls les administrateurs peuvent créer des utilisateurs via /api/users
        return $this->json([
            'error' => 'Public registration is disabled',
            'message' => 'Please contact an administrator to create an account'
        ], Response::HTTP_FORBIDDEN);

        /* CODE ORIGINAL DÉSACTIVÉ POUR SÉCURITÉ
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->json(['error' => 'Email et mot de passe requis'], Response::HTTP_BAD_REQUEST);
        }

        // Valider la force du mot de passe
        $passwordErrors = $this->validatePasswordStrength($data['password']);
        if (!empty($passwordErrors)) {
            return $this->json([
                'error' => 'Password does not meet requirements',
                'details' => $passwordErrors
            ], Response::HTTP_BAD_REQUEST);
        }

        $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->json(['error' => 'Cet email est déjà utilisé'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setEmail($data['email']);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        // SÉCURITÉ: Toujours définir ROLE_USER uniquement, jamais accepter role de la requête
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $token = $this->jwtManager->create($user);

        return $this->json([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'role' => $this->getUserRole($user),
                'createdAt' => $user->getCreatedAt()->format('c')
            ],
            'token' => $token
        ], Response::HTTP_CREATED);
        */
    }

    //  ROUTE DE CONNEXION
    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->json(['error' => 'Email et mot de passe requis'], Response::HTTP_BAD_REQUEST);
        }

        // SÉCURITÉ: Vérifier si l'utilisateur est bloqué suite à trop de tentatives échouées
        if ($this->loginAttemptHandler->isBlocked($data['email'])) {
            $remainingTime = $this->loginAttemptHandler->getLockoutTimeRemaining($data['email']);
            $minutes = ceil($remainingTime / 60);

            return $this->json([
                'error' => 'Trop de tentatives de connexion échouées',
                'message' => "Compte temporairement verrouillé. Réessayez dans {$minutes} minute(s).",
                'lockout_remaining_seconds' => $remainingTime
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $user = $this->userRepository->findOneBy(['email' => $data['email']]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            // SÉCURITÉ: Enregistrer la tentative échouée
            $this->loginAttemptHandler->recordAttempt($data['email']);
            $remainingAttempts = $this->loginAttemptHandler->getRemainingAttempts($data['email']);

            return $this->json([
                'error' => 'Identifiants invalides',
                'remaining_attempts' => $remainingAttempts
            ], Response::HTTP_UNAUTHORIZED);
        }

        // SÉCURITÉ: Connexion réussie, réinitialiser les tentatives
        $this->loginAttemptHandler->resetAttempts($data['email']);

        $token = $this->jwtManager->create($user);

        return $this->json([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'role' => $this->getUserRole($user),
                'createdAt' => $user->getCreatedAt()->format('c')
            ],
            'token' => $token
        ]);
    }

    //  NOUVELLE ROUTE POUR RÉCUPÉRER LES INFOS DE L'UTILISATEUR AUTHENTIFIÉ
    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'role' => $this->getUserRole($user), // 🔥 UTILISATION DE LA NOUVELLE MÉTHODE
            'createdAt' => $user->getCreatedAt()->format('c')
        ]);
    }

    //  MÉTHODE PRIVÉE POUR VALIDER LA FORCE DU MOT DE PASSE
    
    private function validatePasswordStrength(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < 12) {
            $errors[] = "Le mot de passe doit contenir au moins 12 caractères";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins une majuscule";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins une minuscule";
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins un chiffre";
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins un caractère spécial";
        }
        
        // Vérifier si le mot de passe est dans une liste de mots de passe courants
        $commonPasswords = ['Password123!', 'Admin123!', 'Welcome123!'];
        if (in_array($password, $commonPasswords)) {
            $errors[] = "Ce mot de passe est trop commun";
        }
        
        return $errors;
    }

    //  MÉTHODE PRIVÉE POUR DÉTERMINER LE RÔLE
    private function getUserRole(User $user): string
    {
        $roles = $user->getRoles();
        
        // Ordre d'importance: Admin > DPO > User
        if (in_array('ROLE_ADMIN', $roles)) {
            return 'admin';
        }
        
        if (in_array('ROLE_DPO', $roles)) {
            return 'dpo';
        }
        
        return 'user';
    }
}