<?php

namespace App\Service;

use App\Entity\Activity;
use App\Repository\ActivityRepository;
use App\Repository\VoyageRepository;
use Doctrine\ORM\EntityManagerInterface;

class ActivityService
{
    public function __construct(
        private readonly ActivityRepository $activityRepository,
        private readonly VoyageRepository $voyageRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }
    
    /**
     * Create a new activity
     */
    public function createActivity(array $data): ?Activity
    {
        $voyage = $this->voyageRepository->find($data['voyage_id'] ?? 0);
        if (!$voyage) {
            return null;
        }
        
        $activity = new Activity();
        $activity->setVoyage($voyage);
        $activity->setName($data['name'] ?? '');
        $activity->setDescription($data['description'] ?? null);
        $activity->setDurationHours($data['duration_hours'] ?? null);
        $activity->setPricePerPerson($data['price_per_person'] ?? '0.00');
        
        $this->entityManager->persist($activity);
        $this->entityManager->flush();
        
        return $activity;
    }
    
    /**
     * Update an existing activity
     */
    public function updateActivity(int $id, array $data): ?Activity
    {
        $activity = $this->activityRepository->find($id);
        if (!$activity) {
            return null;
        }
        
        if (isset($data['voyage_id'])) {
            $voyage = $this->voyageRepository->find($data['voyage_id']);
            if ($voyage) {
                $activity->setVoyage($voyage);
            }
        }
        if (isset($data['name'])) {
            $activity->setName($data['name']);
        }
        if (isset($data['description'])) {
            $activity->setDescription($data['description']);
        }
        if (isset($data['duration_hours'])) {
            $activity->setDurationHours($data['duration_hours']);
        }
        if (isset($data['price_per_person'])) {
            $activity->setPricePerPerson($data['price_per_person']);
        }
        
        $this->entityManager->flush();
        
        return $activity;
    }
    
    /**
     * Delete an activity
     */
    public function deleteActivity(int $id): bool
    {
        $activity = $this->activityRepository->find($id);
        if (!$activity) {
            return false;
        }
        
        $this->entityManager->remove($activity);
        $this->entityManager->flush();
        
        return true;
    }
    
    /**
     * Get all activities for admin
     */
    public function getAllActivitiesForAdmin(): array
    {
        try {
            $activities = $this->activityRepository->findAll();
        } catch (\Throwable) {
            $activities = [];
        }
        
        $normalized = [];
        foreach ($activities as $activity) {
            $voyage = $activity->getVoyage();
            if ($voyage === null) {
                continue;
            }
            $normalized[] = [
                'id' => $activity->getId(),
                'name' => $activity->getName(),
                'description' => $activity->getDescription(),
                'duration_hours' => $activity->getDurationHours(),
                'price_per_person' => (float) $activity->getPricePerPerson(),
                'voyage_id' => $voyage->getId(),
                'voyage_title' => $voyage->getTitle(),
                'destination' => $voyage->getDestination(),
            ];
        }
        return $normalized;
    }
    
    /**
     * Get activity by ID for admin
     */
    public function getActivityByIdForAdmin(int $id): ?array
    {
        try {
            $activity = $this->activityRepository->find($id);
        } catch (\Throwable) {
            $activity = null;
        }
        
        if ($activity === null) {
            return null;
        }
        
        $voyage = $activity->getVoyage();
        
        return [
            'id' => $activity->getId(),
            'name' => $activity->getName(),
            'description' => $activity->getDescription(),
            'duration_hours' => $activity->getDurationHours(),
            'price_per_person' => (float) $activity->getPricePerPerson(),
            'voyage_id' => $voyage?->getId(),
            'voyage_title' => $voyage?->getTitle(),
            'destination' => $voyage?->getDestination(),
        ];
    }
    
    /**
     * Get activities by voyage ID
     */
    public function getActivitiesByVoyageId(int $voyageId): array
    {
        try {
            $activities = $this->activityRepository->findBy(['voyage' => $voyageId]);
        } catch (\Throwable) {
            $activities = [];
        }
        
        $normalized = [];
        foreach ($activities as $activity) {
            $normalized[] = [
                'id' => $activity->getId(),
                'name' => $activity->getName(),
                'description' => $activity->getDescription(),
                'duration_hours' => $activity->getDurationHours(),
                'price_per_person' => (float) $activity->getPricePerPerson(),
            ];
        }
        return $normalized;
    }
}