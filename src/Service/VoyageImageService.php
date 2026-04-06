<?php

namespace App\Service;

use App\Entity\VoyageImage;
use App\Repository\VoyageImageRepository;
use App\Repository\VoyageRepository;
use Doctrine\ORM\EntityManagerInterface;

class VoyageImageService
{
    public function __construct(
        private readonly VoyageImageRepository $voyageImageRepository,
        private readonly VoyageRepository $voyageRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }
    
    /**
     * Create a new voyage image
     */
    public function createVoyageImage(array $data): ?VoyageImage
    {
        $voyage = $this->voyageRepository->find($data['voyage_id'] ?? 0);
        if (!$voyage) {
            return null;
        }
        
        $image = new VoyageImage();
        $image->setVoyageId($voyage->getId());
        $image->setImageUrl($data['image_url'] ?? '');
        $image->setCloudinaryPublicId($data['cloudinary_public_id'] ?? '');
        $image->setCreatedAt(new \DateTime());
        $image->setUpdatedAt(new \DateTime());
        
        $this->entityManager->persist($image);
        $this->entityManager->flush();
        
        return $image;
    }
    
    /**
     * Update an existing voyage image
     */
    public function updateVoyageImage(int $id, array $data): ?VoyageImage
    {
        $image = $this->voyageImageRepository->find($id);
        if (!$image) {
            return null;
        }
        
        if (isset($data['voyage_id'])) {
            $image->setVoyageId($data['voyage_id']);
        }
        if (isset($data['image_url'])) {
            $image->setImageUrl($data['image_url']);
        }
        if (isset($data['cloudinary_public_id'])) {
            $image->setCloudinaryPublicId($data['cloudinary_public_id']);
        }
        
        $image->setUpdatedAt(new \DateTime());
        
        $this->entityManager->flush();
        
        return $image;
    }
    
    /**
     * Delete a voyage image
     */
    public function deleteVoyageImage(int $id): bool
    {
        $image = $this->voyageImageRepository->find($id);
        if (!$image) {
            return false;
        }
        
        $this->entityManager->remove($image);
        $this->entityManager->flush();
        
        return true;
    }
    
    /**
     * Get all images for admin
     */
    public function getAllImagesForAdmin(): array
    {
        try {
            $images = $this->voyageImageRepository->findAll();
        } catch (\Throwable) {
            $images = [];
        }
        
        $normalized = [];
        foreach ($images as $image) {
            $voyage = $this->voyageRepository->find($image->getVoyageId());
            $normalized[] = [
                'id' => $image->getId(),
                'voyage_id' => $image->getVoyageId(),
                'voyage_title' => $voyage?->getTitle() ?? 'Unknown',
                'image_url' => $image->getImageUrl(),
                'cloudinary_public_id' => $image->getCloudinaryPublicId(),
                'created_at' => $image->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updated_at' => $image->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ];
        }
        return $normalized;
    }
    
    /**
     * Get image by ID for admin
     */
    public function getImageByIdForAdmin(int $id): ?array
    {
        try {
            $image = $this->voyageImageRepository->find($id);
        } catch (\Throwable) {
            $image = null;
        }
        
        if ($image === null) {
            return null;
        }
        
        $voyage = $this->voyageRepository->find($image->getVoyageId());
        
        return [
            'id' => $image->getId(),
            'voyage_id' => $image->getVoyageId(),
            'voyage_title' => $voyage?->getTitle() ?? 'Unknown',
            'image_url' => $image->getImageUrl(),
            'cloudinary_public_id' => $image->getCloudinaryPublicId(),
            'created_at' => $image->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $image->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
    
    /**
     * Get images by voyage ID
     */
    public function getImagesByVoyageId(int $voyageId): array
    {
        try {
            $images = $this->voyageImageRepository->findByVoyageId($voyageId);
        } catch (\Throwable) {
            $images = [];
        }
        
        return array_map(function ($image) {
            if (is_array($image)) {
                return $image;
            }
            return [
                'id' => $image->getId(),
                'image_url' => $image->getImageUrl(),
                'cloudinary_public_id' => $image->getCloudinaryPublicId(),
            ];
        }, $images);
    }
}