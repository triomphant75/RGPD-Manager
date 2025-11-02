<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserDeletionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private UserDeletionService $userDeletionService
    ) {}

    #[Route('', name: 'api_users_list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $users = $this->userRepository->findAll();
        
        $data = array_map(function($user) {
            return $this->formatUserResponse($user);
        }, $users);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'api_users_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // SÉCURITÉ: Les utilisateurs ne peuvent voir que leur propre profil, sauf les admins
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if ($user->getId() !== $currentUser->getId() && !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            return $this->json([
                'error' => 'Accès refusé',
                'message' => 'Vous ne pouvez consulter que votre propre profil'
            ], Response::HTTP_FORBIDDEN);
        }

        return $this->json($this->formatUserResponse($user));
    }

    #[Route('', name: 'api_users_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->json(
                ['error' => 'Email et mot de passe requis'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // SÉCURITÉ: Valider la force du mot de passe
        $passwordErrors = $this->validatePasswordStrength($data['password']);
        if (!empty($passwordErrors)) {
            return $this->json([
                'error' => 'Le mot de passe ne respecte pas les exigences de sécurité',
                'details' => $passwordErrors
            ], Response::HTTP_BAD_REQUEST);
        }

        $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->json(
                ['error' => 'Cet email est déjà utilisé'],
                Response::HTTP_CONFLICT
            );
        }

        $user = new User();
        $user->setEmail($data['email']);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $roles = ['ROLE_USER'];
        if (isset($data['role'])) {
            if ($data['role'] === 'admin') {
                $roles[] = 'ROLE_ADMIN';
            } elseif ($data['role'] === 'dpo') {
                $roles[] = 'ROLE_DPO';
            }
        }
        $user->setRoles($roles);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json(
            $this->formatUserResponse($user),
            Response::HTTP_CREATED
        );
    }

    #[Route('/{id}', name: 'api_users_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['email'])) {
            $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                return $this->json(
                    ['error' => 'Cet email est déjà utilisé'],
                    Response::HTTP_CONFLICT
                );
            }
            $user->setEmail($data['email']);
        }

        if (isset($data['password']) && !empty($data['password'])) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        }

        if (isset($data['role'])) {
            $roles = ['ROLE_USER'];
            if ($data['role'] === 'admin') {
                $roles[] = 'ROLE_ADMIN';
            } elseif ($data['role'] === 'dpo') {
                $roles[] = 'ROLE_DPO';
            }
            $user->setRoles($roles);
        }

        $user->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $this->json($this->formatUserResponse($user));
    }

    /**
     * Supprime un utilisateur de manière conforme RGPD
     *
     * @param int $id ID de l'utilisateur à supprimer
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_users_delete', methods: ['DELETE'])]
    public function delete(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Vérifier si l'utilisateur peut être supprimé
        $canDelete = $this->userDeletionService->canDeleteUser($user, $currentUser);
        if (!$canDelete['can_delete']) {
            return $this->json(
                ['error' => $canDelete['reason']],
                Response::HTTP_FORBIDDEN
            );
        }

        // Récupérer la raison de la suppression si fournie
        $data = json_decode($request->getContent(), true);
        $reason = $data['reason'] ?? 'Suppression par l\'administrateur';

        try {
            // Suppression conforme RGPD
            $result = $this->userDeletionService->deleteUserGDPRCompliant(
                $user,
                $currentUser,
                $reason
            );

            return $this->json([
                'message' => 'Utilisateur supprimé avec succès (conforme RGPD)',
                'details' => [
                    'user_id' => $result['user_id'],
                    'treatments_anonymized' => $result['treatments_anonymized'],
                    'notifications_deleted' => $result['notifications_deleted'],
                    'deleted_at' => $result['deleted_at'],
                ],
            ]);

        } catch (\RuntimeException $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Prévisualise les données qui seront affectées par la suppression
     *
     * @param int $id ID de l'utilisateur
     * @return JsonResponse
     */
    #[Route('/{id}/preview-deletion', name: 'api_users_preview_deletion', methods: ['GET'])]
    public function previewDeletion(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Vérifier si l'utilisateur peut être supprimé
        $canDelete = $this->userDeletionService->canDeleteUser($user, $currentUser);

        $preview = $this->userDeletionService->previewDeletion($user);
        $preview['can_delete'] = $canDelete['can_delete'];
        $preview['deletion_blocked_reason'] = $canDelete['reason'];

        return $this->json($preview);
    }

    /**
     * Valide la force du mot de passe selon les critères de sécurité
     */
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
        $commonPasswords = ['Password123!', 'Admin123!', 'Welcome123!', 'Azerty123!'];
        if (in_array($password, $commonPasswords)) {
            $errors[] = "Ce mot de passe est trop commun";
        }

        return $errors;
    }

    private function formatUserResponse(User $user): array
    {
        $roles = $user->getRoles();

        // Ordre de priorité: Admin > DPO > User
        if (in_array('ROLE_ADMIN', $roles)) {
            $role = 'admin';
        } elseif (in_array('ROLE_DPO', $roles)) {
            $role = 'dpo';
        } else {
            $role = 'user';
        }

        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'role' => $role,
            'createdAt' => $user->getCreatedAt()->format('c'),
            'updatedAt' => $user->getUpdatedAt()->format('c'),
        ];
    }
}