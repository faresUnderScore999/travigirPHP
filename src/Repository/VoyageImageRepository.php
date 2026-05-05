<?php

namespace App\Repository;

use App\Entity\VoyageImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VoyageImage>
 */
class VoyageImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VoyageImage::class);
    }

    /**
     * Find all images for a specific voyage
     *
     * @return VoyageImage[]
     */
   public function findByVoyageId(int $voyageId): array
    {
        $images = $this->findBy(['voyageId' => $voyageId]);

        if (empty($images)) {
            return $this->getDefaultImages();
        }

        return $images;
    }

    /**
     * Load images for multiple voyages in a single query (avoids N+1).
     * Returns a map of voyageId => VoyageImage[].
     *
     * @param int[] $voyageIds
     * @return array<int, VoyageImage[]>
     */
    public function findByVoyageIds(array $voyageIds): array
    {
        if (empty($voyageIds)) {
            return [];
        }

        $images = $this->createQueryBuilder('vi')
            ->where('vi.voyageId IN (:ids)')
            ->setParameter('ids', $voyageIds)
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($images as $image) {
            $map[$image->getVoyageId()][] = $image;
        }

        return $map;
    }

    /**
     * Get default images when no voyage images are found
     */
private function getDefaultImages(): array
{
    $defaultImage = new VoyageImage();
    $defaultImage->setVoyageId(0); // or null if your entity allows
    $defaultImage->setImageUrl('https://cratertravelagencies.com/assets/img/crater5.jpg');
    $defaultImage->setCloudinaryPublicId('default');
    $defaultImage->setCreatedAt(new \DateTime());
    $defaultImage->setUpdatedAt(new \DateTime());
    
    return [$defaultImage];
}
}