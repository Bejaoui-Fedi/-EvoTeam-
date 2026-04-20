<?php

namespace App\Service;

use App\Entity\UserProfile;
use Doctrine\ORM\EntityManagerInterface;

class UserProfileService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Create a new UserProfile
     */
    public function createUserProfile(UserProfile $userProfile): UserProfile
    {
        $this->entityManager->persist($userProfile);
        $this->entityManager->flush();

        return $userProfile;
    }

    /**
     * Read a UserProfile by ID
     */
    public function getUserProfile(int $id): ?UserProfile
    {
        return $this->entityManager->getRepository(UserProfile::class)->find($id);
    }

    /**
     * Read all UserProfiles
     * 
     * @return UserProfile[]
     */
    public function getAllUserProfiles(): array
    {
        return $this->entityManager->getRepository(UserProfile::class)->findAll();
    }

    /**
     * Update an existing UserProfile
     */
    public function updateUserProfile(UserProfile $userProfile): UserProfile
    {
        // Update the modification date automatically before saving
        $userProfile->setDateModification(new \DateTime());
        
        $this->entityManager->flush();

        return $userProfile;
    }

    /**
     * Delete a UserProfile
     */
    public function deleteUserProfile(UserProfile $userProfile): void
    {
        $this->entityManager->remove($userProfile);
        $this->entityManager->flush();
    }
    
    /**
     * Find UserProfile by User ID, creating it if it doesn't exist
     */
    public function findOrCreateByUserId(\App\Entity\User $user): UserProfile
    {
        $userProfile = $this->findByUserId($user->getId());
        
        if (!$userProfile) {
            $userProfile = new UserProfile();
            $userProfile->setUser($user);
            $userProfile->setDateCreation(new \DateTime());
            $userProfile->setNotificationsEmail(true);
            $userProfile->setNotificationsSms(false);
            
            $this->entityManager->persist($userProfile);
            $this->entityManager->flush();
        }
        
        return $userProfile;
    }

    /**
     * Find UserProfile by User ID
     */
    public function findByUserId(int $userId): ?UserProfile
    {
        return $this->entityManager->getRepository(UserProfile::class)->findOneBy(['user' => $userId]);
    }
}
