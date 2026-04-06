<?php

namespace App\Service;

use App\Entity\Voyage;
use App\Repository\VoyageImageRepository;
use App\Repository\VoyageRepository;
use Doctrine\ORM\EntityManagerInterface;

class VoyageService
{
    public function __construct(
        private readonly VoyageRepository $voyageRepository,
        private readonly VoyageImageRepository $voyageImageRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }
    
    /**
     * Create a new voyage
     */
    public function createVoyage(array $data): Voyage
    {
        $voyage = new Voyage();
        $voyage->setTitle($data['title'] ?? '');
        $voyage->setDescription($data['description'] ?? null);
        $voyage->setDestination($data['destination'] ?? '');
        $voyage->setStartDate(isset($data['start_date']) ? new \DateTime($data['start_date']) : null);
        $voyage->setEndDate(isset($data['end_date']) ? new \DateTime($data['end_date']) : null);
        $voyage->setPrice($data['price'] ?? null);
        $voyage->setImageUrl($data['image_url'] ?? []);
        $voyage->setCreatedAt(new \DateTime());
        
        $this->entityManager->persist($voyage);
        $this->entityManager->flush();
        
        return $voyage;
    }
    
    /**
     * Update an existing voyage
     */
    public function updateVoyage(int $id, array $data): ?Voyage
    {
        $voyage = $this->voyageRepository->find($id);
        if (!$voyage) {
            return null;
        }
        
        if (isset($data['title'])) {
            $voyage->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $voyage->setDescription($data['description']);
        }
        if (isset($data['destination'])) {
            $voyage->setDestination($data['destination']);
        }
        if (isset($data['start_date'])) {
            $voyage->setStartDate(new \DateTime($data['start_date']));
        }
        if (isset($data['end_date'])) {
            $voyage->setEndDate(new \DateTime($data['end_date']));
        }
        if (isset($data['price'])) {
            $voyage->setPrice($data['price']);
        }
        if (isset($data['image_url'])) {
            $voyage->setImageUrl($data['image_url']);
        }
        
        $this->entityManager->flush();
        
        return $voyage;
    }
    
    /**
     * Delete a voyage
     */
    public function deleteVoyage(int $id): bool
    {
        $voyage = $this->voyageRepository->find($id);
        if (!$voyage) {
            return false;
        }
        
        $this->entityManager->remove($voyage);
        $this->entityManager->flush();
        
        return true;
    }
    
    /**
     * Get all voyages for admin
     */
    public function getAllVoyagesForAdmin(): array
    {
        try {
            $voyages = $this->voyageRepository->findAllOrdered();
        } catch (\Throwable) {
            $voyages = [];
        }
        
        return array_map(fn ($voyage) => $this->mapVoyageForAdmin($voyage), $voyages);
    }
    
    /**
     * Get voyage by ID for admin
     */
    public function getVoyageByIdForAdmin(int $id): ?array
    {
        try {
            $voyage = $this->voyageRepository->find($id);
        } catch (\Throwable) {
            $voyage = null;
        }
        
        if ($voyage !== null) {
            return $this->mapVoyageForAdmin($voyage);
        }
        
        return null;
    }
    
    private function mapVoyageForAdmin(object $voyage): array
    {
        $images = $this->voyageImageRepository->findByVoyageId($voyage->getId());
        $imageUrls = array_map(function ($image) {
            if (is_array($image)) {
                return $image['imageUrl'] ?? '';
            }
            return $image->getImageUrl();
        }, $images);
        
        return [
            'id' => $voyage->getId(),
            'title' => $voyage->getTitle(),
            'description' => $voyage->getDescription(),
            'destination' => $voyage->getDestination(),
            'start_date' => $voyage->getStartDate()?->format('Y-m-d'),
            'end_date' => $voyage->getEndDate()?->format('Y-m-d'),
            'price' => $voyage->getPrice(),
            'image_url' => $imageUrls,
            'created_at' => $voyage->getCreatedAt()?->format('Y-m-d H:i:s'),
            'activities_count' => $voyage->getActivities()->count(),
            'offers_count' => $voyage->getOffers()->count(),
        ];
    }

    public function getFeaturedVoyages(int $limit = 3): array
    {
        try {
            $voyages = $this->voyageRepository->findFeatured($limit);
        } catch (\Throwable) {
            $voyages = [];
        }

        return array_map(fn ($voyage) => $this->mapVoyage($voyage), $voyages);
    }

    public function getAllVoyages(): array
    {
        try {
            $voyages = $this->voyageRepository->findAllOrdered();
        } catch (\Throwable) {
            $voyages = [];
        }

        return array_map(fn ($voyage) => $this->mapVoyage($voyage), $voyages);
    }

    public function getVoyages(int $page = 1, int $limit = 12): array
    {
        try {
            $offset = ($page - 1) * $limit;
            $voyages = $this->voyageRepository->findBy([], ['createdAt' => 'DESC'], $limit, $offset);
        } catch (\Throwable) {
            $voyages = [];
        }

        return array_map(fn ($voyage) => $this->mapVoyage($voyage), $voyages);
    }

    public function getTotalVoyages(): int
    {
        try {
            return $this->voyageRepository->count([]);
        } catch (\Throwable) {
            return 0;
        }
    }

    public function getVoyageById(int $id): ?array
    {
        try {
            $voyage = $this->voyageRepository->find($id);
        } catch (\Throwable) {
            $voyage = null;
        }

        if ($voyage !== null) {
            $mapped = $this->mapVoyage($voyage);
            $mapped['activities'] = [];

            foreach ($voyage->getActivities() as $activity) {
                $mapped['activities'][] = [
                    'name' => $activity->getName(),
                    'description' => $activity->getDescription(),
                    'duration_hours' => $activity->getDurationHours(),
                    'price_per_person' => $activity->getPricePerPerson(),
                ];
            }

            return $mapped;
        }

        return null;
    }

    private function mapVoyage(object $voyage): array
    {
        // Fetch images from voyage_images table
        $images = $this->voyageImageRepository->findByVoyageId($voyage->getId());

        $imageUrls = array_map(function ($image) {
            // Handle both VoyageImage objects and plain arrays (from getDefaultImages)
            if (is_array($image)) {
                return $image['imageUrl'] ?? '';
            }
            return $image->getImageUrl();
        }, $images);

        return [
            'id' => $voyage->getId(),
            'title' => $voyage->getTitle(),
            'description' => $voyage->getDescription(),
            'destination' => $voyage->getDestination(),
            'start_date' => $voyage->getStartDate()?->format('Y-m-d'),
            'end_date' => $voyage->getEndDate()?->format('Y-m-d'),
            'price' => $voyage->getPrice(),
            'image_url' => $imageUrls,
        ];
    }
}
