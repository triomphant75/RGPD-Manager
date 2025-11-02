<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Trouve tous les utilisateurs ayant un rôle spécifique
     * Méthode simple : récupérer tous les users et filtrer en PHP
     */
    public function findByRole(string $role): array
    {
        $allUsers = $this->findAll();
        
        return array_filter($allUsers, function(User $user) use ($role) {
            return in_array($role, $user->getRoles(), true);
        });
    }

    public function findAdmins(): array
    {
        return $this->findByRole('ROLE_ADMIN');
    }

    public function findDPOs(): array
    {
        return $this->findByRole('ROLE_DPO');
    }
}