<?php

namespace App\Service;

use App\Entity\Offer;
use App\Entity\Voyage;
use App\Repository\OfferRepository;
use App\Repository\VoyageRepository;
use Doctrine\ORM\EntityManagerInterface;

class OfferService
{
    public function __construct(
        private readonly OfferRepository $offerRepository,
        private readonly VoyageRepository $voyageRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }
    
    /**
     * Create a new offer
     */
    public function createOffer(array $data): ?Offer
    {
        $voyage = $this->voyageRepository->find($data['voyage_id'] ?? 0);
        if (!$voyage) {
            return null;
        }
        
        $offer = new Offer();
        $offer->setVoyage($voyage);
        $offer->setTitle($data['title'] ?? '');
        $offer->setDescription($data['description'] ?? null);
        $offer->setDiscountPercentage($data['discount_percentage'] ?? null);
        $offer->setStartDate(isset($data['start_date']) ? new \DateTime($data['start_date']) : null);
        $offer->setEndDate(isset($data['end_date']) ? new \DateTime($data['end_date']) : null);
        $offer->setIsActive($data['is_active'] ?? true);
        
        $this->entityManager->persist($offer);
        $this->entityManager->flush();
        
        return $offer;
    }
    
    /**
     * Update an existing offer
     */
    public function updateOffer(int $id, array $data): ?Offer
    {
        $offer = $this->offerRepository->find($id);
        if (!$offer) {
            return null;
        }
        
        if (isset($data['voyage_id'])) {
            $voyage = $this->voyageRepository->find($data['voyage_id']);
            if ($voyage) {
                $offer->setVoyage($voyage);
            }
        }
        if (isset($data['title'])) {
            $offer->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $offer->setDescription($data['description']);
        }
        if (isset($data['discount_percentage'])) {
            $offer->setDiscountPercentage($data['discount_percentage']);
        }
        if (isset($data['start_date'])) {
            $offer->setStartDate(new \DateTime($data['start_date']));
        }
        if (isset($data['end_date'])) {
            $offer->setEndDate(new \DateTime($data['end_date']));
        }
        if (isset($data['is_active'])) {
            $offer->setIsActive($data['is_active']);
        }
        
        $this->entityManager->flush();
        
        return $offer;
    }
    
    /**
     * Delete an offer
     */
    public function deleteOffer(int $id): bool
    {
        $offer = $this->offerRepository->find($id);
        if (!$offer) {
            return false;
        }
        
        $this->entityManager->remove($offer);
        $this->entityManager->flush();
        
        return true;
    }
    
    /**
     * Get all offers for admin
     */
    public function getAllOffersForAdmin(): array
    {
        try {
            $offers = $this->offerRepository->findAll();
        } catch (\Throwable) {
            $offers = [];
        }
        
        $normalized = [];
        foreach ($offers as $offer) {
            $voyage = $offer->getVoyage();
            if ($voyage === null) {
                continue;
            }
            $normalized[] = [
                'id' => $offer->getId(),
                'title' => $offer->getTitle(),
                'description' => $offer->getDescription(),
                'discount_percentage' => (float) ($offer->getDiscountPercentage() ?? 0),
                'start_date' => $offer->getStartDate()?->format('Y-m-d'),
                'end_date' => $offer->getEndDate()?->format('Y-m-d'),
                'is_active' => $offer->isActive(),
                'voyage_id' => $voyage->getId(),
                'voyage_title' => $voyage->getTitle(),
                'destination' => $voyage->getDestination(),
            ];
        }
        return $normalized;
    }
    
    /**
     * Get offer by ID for admin
     */
    public function getOfferByIdForAdmin(int $id): ?array
    {
        try {
            $offer = $this->offerRepository->find($id);
        } catch (\Throwable) {
            $offer = null;
        }
        
        if ($offer === null) {
            return null;
        }
        
        $voyage = $offer->getVoyage();
        
        return [
            'id' => $offer->getId(),
            'title' => $offer->getTitle(),
            'description' => $offer->getDescription(),
            'discount_percentage' => (float) ($offer->getDiscountPercentage() ?? 0),
            'start_date' => $offer->getStartDate()?->format('Y-m-d'),
            'end_date' => $offer->getEndDate()?->format('Y-m-d'),
            'is_active' => $offer->isActive(),
            'voyage_id' => $voyage?->getId(),
            'voyage_title' => $voyage?->getTitle(),
            'destination' => $voyage?->getDestination(),
        ];
    }

    public function getActiveOffers(): array
    {
        try {
            $offers = $this->offerRepository->findActiveOffers();
        } catch (\Throwable) {
            $offers = [];
        }

  

        $normalized = [];

        foreach ($offers as $offer) {
            $voyage = $offer->getVoyage();
            if ($voyage === null) {
                continue;
            }

            $normalized[] = [
                'id' => $offer->getId(),
                'title' => $offer->getTitle(),
                'description' => $offer->getDescription(),
                'discount_percentage' => (float) ($offer->getDiscountPercentage() ?? 0),
                'start_date' => $offer->getStartDate()?->format('Y-m-d'),
                'end_date' => $offer->getEndDate()?->format('Y-m-d'),
                'voyage_id' => $voyage->getId(),
                'voyage_title' => $voyage->getTitle(),
                'destination' => $voyage->getDestination(),
                'price' => (float) ($voyage->getPrice() ?? 0),
                'image_url' => ($voyage->getImageUrl()[0] ?? null) ?? 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?auto=format&amp;fit=crop&amp;w=1200&amp;q=80',
            ];
        }

        return $normalized;
    }

 
}