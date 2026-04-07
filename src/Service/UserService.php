<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Create a new User
     */
    public function createUser(User $user): User
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * Read a User by ID
     */
    public function getUser(int $id): ?User
    {
        return $this->entityManager->getRepository(User::class)->find($id);
    }

    /**
     * Read all Users
     * 
     * @return User[]
     */
    public function getAllUsers(): array
    {
        return $this->entityManager->getRepository(User::class)->findAll();
    }

    /**
     * Search Users by query
     * 
     * @return User[]
     */
    public function searchUsers(string $query): array
    {
        return $this->entityManager->getRepository(User::class)->search($query);
    }
    
    /**
     * Search Users by query and role
     * 
     * @return User[]
     */
    public function searchUsersWithRole(string $query, string $role): array
    {
        return $this->entityManager->getRepository(User::class)->searchWithRole($query, $role);
    }

    /**
     * Update an existing User
     */
    public function updateUser(User $user): User
    {
        // Doctrine tracks changes automatically, so we just need to flush
        $this->entityManager->flush();

        return $user;
    }

    /**
     * Delete a User
     */
    public function deleteUser(User $user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
    }
}
