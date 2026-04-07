<?php

namespace App\Controller;

use App\Service\VoyageService;
use App\Service\OfferService;
use App\Service\ActivityService;
use App\Service\VoyageImageService;
use App\Service\ValidationService;
use App\Repository\VoyageRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    public function __construct(
        private VoyageService $voyageService,
        private OfferService $offerService,
        private ActivityService $activityService,
        private VoyageImageService $voyageImageService,
        private VoyageRepository $voyageRepository,
        private ValidationService $validationService,
        private ?LoggerInterface $logger = null,
    ) {}

// ==================== VOYAGES ====================

#[Route('/voyages', name: 'admin_voyages', methods: ['GET'])]
public function voyages(Request $request): Response
{
    $search = $request->query->get('search', '');
    $minPrice = $request->query->get('min_price');
    $maxPrice = $request->query->get('max_price');
    $startDateFrom = $request->query->get('start_date_from');
    $startDateTo = $request->query->get('start_date_to');
    $sortBy = $request->query->get('sort_by', 'startDate');
    $sortOrder = $request->query->get('sort_order', 'ASC');
    
    $filters = [];
    
    // Build search filters
    if (!empty($search)) {
        $filters['title'] = $search;
        $filters['destination'] = $search;
    }
    if (!empty($minPrice)) {
        $filters['min_price'] = $minPrice;
    }
    if (!empty($maxPrice)) {
        $filters['max_price'] = $maxPrice;
    }
    if (!empty($startDateFrom)) {
        $filters['start_date_from'] = $startDateFrom;
    }
    if (!empty($startDateTo)) {
        $filters['start_date_to'] = $startDateTo;
    }
    $filters['sort_by'] = $sortBy;
    $filters['sort_order'] = $sortOrder;
    
    // Use search if filters are applied, otherwise get all
    if (!empty($search) || !empty($minPrice) || !empty($maxPrice) || !empty($startDateFrom) || !empty($startDateTo)) {
        $this->logger?->info('Admin searching voyages', $filters);
        $voyages = $this->voyageService->searchVoyages($filters);
    } else {
        $voyages = $this->voyageService->getAllVoyagesForAdmin();
    }
    
    return $this->render('admin/voyages.html.twig', [
        'voyages' => $voyages,
        'search' => $search,
        'filters' => $filters,
    ]);
}

#[Route('/voyages/new', name: 'admin_voyage_new', methods: ['GET', 'POST'])]
public function newVoyage(Request $request): Response
{
    if ($request->isMethod('POST')) {
        $data = $request->request->all();
        $imageUrl = $data['image_url'] ?? [];
        if (is_string($imageUrl)) {
            $imageUrl = array_filter(array_map('trim', explode("\n", $imageUrl)));
        }
        $data['image_url'] = $imageUrl;

        // Validate voyage data
        $this->validationService->validateVoyage($data);
        
        if (!$this->validationService->isValid()) {
            $this->logger?->warning('Validation failed for new voyage', $this->validationService->getErrors());
            foreach ($this->validationService->getErrors() as $field => $errors) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
            }
            return $this->render('admin/voyage_form.html.twig', [
                'voyage' => $data,
                'voyages' => $this->voyageRepository->findAll(),
                'errors' => $this->validationService->getErrors(),
            ]);
        }

        $this->logger?->info('Creating new voyage', ['title' => $data['title'] ?? '']);
        $voyage = $this->voyageService->createVoyage($data);
        $this->addFlash('success', 'Voyage created successfully!');
        return $this->redirectToRoute('admin_voyages');
    }

    return $this->render('admin/voyage_form.html.twig', [
        'voyage' => null,
        'voyages' => $this->voyageRepository->findAll(),
    ]);
}

    #[Route('/voyages/{id}/manage', name: 'admin_voyage_manage', methods: ['GET'])]
    public function manageVoyage(int $id): Response
    {
        $voyage = $this->voyageService->getVoyageByIdForAdmin($id);
        
        if (!$voyage) {
            throw $this->createNotFoundException('Voyage not found');
        }
        
        // Get offers, activities, and images for this voyage
        $offers = $this->offerService->getAllOffersForAdmin();
        $voyageOffers = array_filter($offers, fn($o) => (int) $o['voyage_id'] === $id);
        
        $activities = $this->activityService->getAllActivitiesForAdmin();
        $voyageActivities = array_filter($activities, fn($a) => (int) $a['voyage_id'] === $id);
        
        $images = $this->voyageImageService->getAllImagesForAdmin();
        $voyageImages = array_filter($images, fn($i) => (int) $i['voyage_id'] === $id);
        
        return $this->render('admin/voyage_manage.html.twig', [
            'voyage' => $voyage,
            'offers' => array_values($voyageOffers),
            'activities' => array_values($voyageActivities),
            'images' => array_values($voyageImages),
            'voyage_id' => $id,
        ]);
    }

    #[Route('/voyages/{id}/edit', name: 'admin_voyage_edit', methods: ['GET', 'POST'])]
    public function editVoyage(Request $request, int $id): Response
    {
        $voyage = $this->voyageService->getVoyageByIdForAdmin($id);
        
        if (!$voyage) {
            throw $this->createNotFoundException('Voyage not found');
        }
        
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $imageUrl = $data['image_url'] ?? [];
            if (is_string($imageUrl)) {
                $imageUrl = array_filter(array_map('trim', explode("\n", $imageUrl)));
            }
            $data['image_url'] = $imageUrl;
            
            $this->voyageService->updateVoyage($id, $data);
            $this->addFlash('success', 'Voyage updated successfully!');
            return $this->redirectToRoute('admin_voyages');
        }
        
        return $this->render('admin/voyage_form.html.twig', [
            'voyage' => $voyage,
            'voyages' => $this->voyageRepository->findAll(),
        ]);
    }

    #[Route('/voyages/{id}/delete', name: 'admin_voyage_delete', methods: ['GET', 'POST'])]
    public function deleteVoyage(int $id): Response
    {
        $this->voyageService->deleteVoyage($id);
        $this->addFlash('success', 'Voyage deleted successfully!');
        return $this->redirectToRoute('admin_voyages');
    }

    // ==================== OFFERS ====================
    
    #[Route('/offers', name: 'admin_offers', methods: ['GET'])]
    public function offers(): Response
    {
        $offers = $this->offerService->getAllOffersForAdmin();
        return $this->render('admin/offers.html.twig', [
            'offers' => $offers,
        ]);
    }

    #[Route('/offers/new', name: 'admin_offer_new', methods: ['GET', 'POST'])]
    public function newOffer(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $data['is_active'] = $request->request->get('is_active', '1') === '1';
            
            $offer = $this->offerService->createOffer($data);
            if ($offer) {
                $this->addFlash('success', 'Offer created successfully!');
            } else {
                $this->addFlash('error', 'Failed to create offer. Please select a valid voyage.');
            }
            return $this->redirectToRoute('admin_offers');
        }
        
        return $this->render('admin/offer_form.html.twig', [
            'offer' => null,
            'voyages' => $this->voyageRepository->findAll(),
        ]);
    }

    #[Route('/offers/{id}/edit', name: 'admin_offer_edit', methods: ['GET', 'POST'])]
    public function editOffer(Request $request, int $id): Response
    {
        $offer = $this->offerService->getOfferByIdForAdmin($id);
        
        if (!$offer) {
            throw $this->createNotFoundException('Offer not found');
        }
        
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $data['is_active'] = $request->request->get('is_active', '1') === '1';
            
            $this->offerService->updateOffer($id, $data);
            $this->addFlash('success', 'Offer updated successfully!');
            return $this->redirectToRoute('admin_offers');
        }
        
        return $this->render('admin/offer_form.html.twig', [
            'offer' => $offer,
            'voyages' => $this->voyageRepository->findAll(),
        ]);
    }

    #[Route('/offers/{id}/delete', name: 'admin_offer_delete', methods: ['GET', 'POST'])]
    public function deleteOffer(int $id): Response
    {
        $this->offerService->deleteOffer($id);
        $this->addFlash('success', 'Offer deleted successfully!');
        return $this->redirectToRoute('admin_offers');
    }

    // ==================== ACTIVITIES ====================
    
    #[Route('/activities', name: 'admin_activities', methods: ['GET'])]
    public function activities(): Response
    {
        $activities = $this->activityService->getAllActivitiesForAdmin();
        return $this->render('admin/activities.html.twig', [
            'activities' => $activities,
        ]);
    }

    #[Route('/activities/new', name: 'admin_activity_new', methods: ['GET', 'POST'])]
    public function newActivity(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            $activity = $this->activityService->createActivity($data);
            if ($activity) {
                $this->addFlash('success', 'Activity created successfully!');
            } else {
                $this->addFlash('error', 'Failed to create activity. Please select a valid voyage.');
            }
            return $this->redirectToRoute('admin_activities');
        }
        
        return $this->render('admin/activity_form.html.twig', [
            'activity' => null,
            'voyages' => $this->voyageRepository->findAll(),
        ]);
    }

    #[Route('/activities/{id}/edit', name: 'admin_activity_edit', methods: ['GET', 'POST'])]
    public function editActivity(Request $request, int $id): Response
    {
        $activity = $this->activityService->getActivityByIdForAdmin($id);
        
        if (!$activity) {
            throw $this->createNotFoundException('Activity not found');
        }
        
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            $this->activityService->updateActivity($id, $data);
            $this->addFlash('success', 'Activity updated successfully!');
            return $this->redirectToRoute('admin_activities');
        }
        
        return $this->render('admin/activity_form.html.twig', [
            'activity' => $activity,
            'voyages' => $this->voyageRepository->findAll(),
        ]);
    }

    #[Route('/activities/{id}/delete', name: 'admin_activity_delete', methods: ['GET', 'POST'])]
    public function deleteActivity(int $id): Response
    {
        $this->activityService->deleteActivity($id);
        $this->addFlash('success', 'Activity deleted successfully!');
        return $this->redirectToRoute('admin_activities');
    }

    // ==================== IMAGES ====================
    
    #[Route('/images', name: 'admin_images', methods: ['GET'])]
    public function images(): Response
    {
        $images = $this->voyageImageService->getAllImagesForAdmin();
        return $this->render('admin/images.html.twig', [
            'images' => $images,
        ]);
    }

    #[Route('/images/new', name: 'admin_image_new', methods: ['GET', 'POST'])]
    public function newImage(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            $image = $this->voyageImageService->createVoyageImage($data);
            if ($image) {
                $this->addFlash('success', 'Image created successfully!');
            } else {
                $this->addFlash('error', 'Failed to create image. Please select a valid voyage.');
            }
            return $this->redirectToRoute('admin_images');
        }
        
        return $this->render('admin/image_form.html.twig', [
            'image' => null,
            'voyages' => $this->voyageRepository->findAll(),
        ]);
    }

    #[Route('/images/{id}/edit', name: 'admin_image_edit', methods: ['GET', 'POST'])]
    public function editImage(Request $request, int $id): Response
    {
        $image = $this->voyageImageService->getImageByIdForAdmin($id);
        
        if (!$image) {
            throw $this->createNotFoundException('Image not found');
        }
        
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            $this->voyageImageService->updateVoyageImage($id, $data);
            $this->addFlash('success', 'Image updated successfully!');
            return $this->redirectToRoute('admin_images');
        }
        
        return $this->render('admin/image_form.html.twig', [
            'image' => $image,
            'voyages' => $this->voyageRepository->findAll(),
        ]);
    }

    #[Route('/images/{id}/delete', name: 'admin_image_delete', methods: ['GET', 'POST'])]
    public function deleteImage(int $id): Response
    {
        $this->voyageImageService->deleteVoyageImage($id);
        $this->addFlash('success', 'Image deleted successfully!');
        return $this->redirectToRoute('admin_images');
    }
}