<?php

namespace App\Service;

use App\Repository\OfferRepository;

class OfferService
{
    public function __construct(
        private readonly OfferRepository $offerRepository,
    ) {
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