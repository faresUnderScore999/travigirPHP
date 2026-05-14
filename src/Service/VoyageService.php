<?php

namespace App\Service;

use App\Entity\Voyage;
use App\Repository\VoyageImageRepository;
use App\Repository\VoyageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class VoyageService
{
    public function __construct(
        private readonly VoyageRepository $voyageRepository,
        private readonly VoyageImageRepository $voyageImageRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
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
       // $voyage->setImageUrl($data['image_url'] ?? []);
        $voyage->setCreatedAt(new \DateTime());

        $this->entityManager->persist($voyage);
        $this->entityManager->flush();

        return $voyage;
    }

    /**
     * @param array<string, mixed> $data
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
     * @return array<int, array<string, mixed>>
     */
    public function getAllVoyagesForAdmin(): array
    {
        $voyages = $this->safeExecute(fn () => $this->voyageRepository->findAllOrdered());

        return $this->mapVoyagesWithImages($voyages, true);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getVoyageByIdForAdmin(int $id): ?array
    {
        $voyage = $this->safeExecute(fn () => $this->voyageRepository->find($id), null);

        if ($voyage instanceof \App\Entity\Voyage) {
            return $this->mapVoyageForAdmin($voyage);
        }

        return null;
    }

    /**
     * @param \App\Entity\Voyage[] $voyages
     * @return array<int, array<string, mixed>>
     */
    private function mapVoyagesWithImages(array $voyages, bool $forAdmin = false): array
    {
        if (empty($voyages)) {
            return [];
        }

        $ids = array_map(fn ($v) => (int) $v->getId(), $voyages);
        $imagesMap = $this->safeExecute(
            fn () => $this->voyageImageRepository->findByVoyageIds($ids),
            []
        );

        return array_map(
            fn ($voyage) => $forAdmin
                ? $this->mapVoyageForAdmin($voyage, $imagesMap[(int) $voyage->getId()] ?? [])
                : $this->mapVoyage($voyage, $imagesMap[(int) $voyage->getId()] ?? []),
            $voyages
        );
    }

    /**
     * @param \App\Entity\VoyageImage[] $preloadedImages
     * @return array<string, mixed>
     */
    private function mapVoyageForAdmin(\App\Entity\Voyage $voyage, array $preloadedImages = []): array
    {
        $imageUrls = array_map(fn ($img) => $img->getImageUrl(), $preloadedImages)
            ?: $this->extractImageUrls((int) $voyage->getId());

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

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFeaturedVoyages(int $limit = 3): array
    {
        $voyages = $this->safeExecute(fn () => $this->voyageRepository->findFeatured($limit));

        return $this->mapVoyagesWithImages($voyages);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAllVoyages(): array
    {
        $voyages = $this->safeExecute(fn () => $this->voyageRepository->findAllOrdered());

        return $this->mapVoyagesWithImages($voyages);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getVoyages(int $page = 1, int $limit = 12): array
    {
        $voyages = $this->safeExecute(fn () => $this->voyageRepository->findBy([], ['createdAt' => 'DESC'], $limit, ($page - 1) * $limit));

        return $this->mapVoyagesWithImages($voyages);
    }

    public function getTotalVoyages(): int
    {
        return $this->safeExecute(fn () => $this->voyageRepository->count([]), 0);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getVoyageById(int $id): ?array
    {
        $voyage = $this->safeExecute(fn () => $this->voyageRepository->find($id), null);

        if ($voyage instanceof \App\Entity\Voyage) {
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

    /**
     * @param \App\Entity\VoyageImage[] $preloadedImages
     * @return array<string, mixed>
     */
    private function mapVoyage(\App\Entity\Voyage $voyage, array $preloadedImages = []): array
    {
        $imageUrls = array_map(fn ($img) => $img->getImageUrl(), $preloadedImages)
            ?: $this->extractImageUrls((int) $voyage->getId());

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

    /**
     * Extract image URLs from voyage images repository
     * @return string[]
     */
    public function extractImageUrls(int $voyageId): array
    {
        $images = $this->safeExecute(fn () => $this->voyageImageRepository->findByVoyageId($voyageId), []);

        return array_map(fn ($image) => $image->getImageUrl(), $images);
    }

    /**
     * Safely execute a callback with error handling
     * @template T
     * @param callable(): T $callback
     * @param T $default
     * @return T
     */
    private function safeExecute(callable $callback, mixed $default = []): mixed
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            $this->logger?->error('Service error', ['error' => $e->getMessage()]);
            return $default;
        }
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function searchVoyages(array $filters): array
    {
        $this->logger?->info('Searching voyages with filters', $filters);
        $voyages = $this->safeExecute(fn () => $this->voyageRepository->search($filters));

        return array_map(fn ($voyage) => $this->mapVoyage($voyage), $voyages);
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function countSearchResults(array $filters): int
    {
        return $this->safeExecute(fn () => $this->voyageRepository->countSearch($filters), 0);
    }
}