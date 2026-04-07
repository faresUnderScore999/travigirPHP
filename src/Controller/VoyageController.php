<?php

namespace App\Controller;

use App\Service\VoyageService;
use App\Service\OfferService;
use App\Utility\DatabaseInitializer;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VoyageController extends AbstractController
{
    public function __construct(
        private readonly VoyageService $voyageService,
        private readonly OfferService $offerService,
        private readonly DatabaseInitializer $databaseInitializer,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    #[Route('/', name: 'travel_home', methods: ['GET'])]
    public function home(): Response
    {
        $this->databaseInitializer->ensureSchema();

        return $this->render('travel/home.html.twig', [
            'active_nav' => 'home',
            'featured_voyages' => $this->voyageService->getFeaturedVoyages(6),
        ]);
    }

#[Route('/voyages', name: 'travel_voyages', methods: ['GET'])]
public function voyages(Request $request): Response
{
    $page = $request->query->getInt('page', 1);
    $limit = 12;
    
    // Get search and filter parameters
    $search = $request->query->get('search', '');
    $minPrice = $request->query->get('min_price');
    $maxPrice = $request->query->get('max_price');
    $sortBy = $request->query->get('sort_by', 'startDate');
    $sortOrder = $request->query->get('sort_order', 'ASC');
    
    $filters = [
        'sort_by' => $sortBy,
        'sort_order' => $sortOrder,
        'limit' => $limit,
        'offset' => ($page - 1) * $limit,
    ];
    
    // Build search filters
    if (!empty($search)) {
        $filters['destination'] = $search;
        $filters['title'] = $search;
    }
    if (!empty($minPrice)) {
        $filters['min_price'] = $minPrice;
    }
    if (!empty($maxPrice)) {
        $filters['max_price'] = $maxPrice;
    }
    
    // Use search if filters are applied
    if (!empty($search) || !empty($minPrice) || !empty($maxPrice)) {
        $this->logger?->info('Public searching voyages', $filters);
        $voyages = $this->voyageService->searchVoyages($filters);
        $totalVoyages = $this->voyageService->countSearchResults($filters);
    } else {
        $voyages = $this->voyageService->getVoyages($page, $limit);
        $totalVoyages = $this->voyageService->getTotalVoyages();
    }
    
    $totalPages = ceil($totalVoyages / $limit) ?: 1;

    return $this->render('travel/voyages.html.twig', [
        'active_nav' => 'voyages',
        'voyages' => $voyages,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'search' => $search,
        'filters' => $filters,
    ]);
}

    #[Route('/voyages/{id}', name: 'travel_voyage_detail', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function voyageDetail(int $id): Response
    {
        $voyage = $this->voyageService->getVoyageById($id);

        if ($voyage === null) {
            throw $this->createNotFoundException('Voyage not found');
        }

        $offers = $this->offerService->getActiveOffers();
        $offerForVoyage = array_filter($offers, fn($o) => (int) $o['voyage_id'] === $id);
        $offer = $offerForVoyage ? array_values($offerForVoyage)[0] : null;

        return $this->render('travel/voyage_detail.html.twig', [
            'active_nav' => 'voyages',
            'voyage' => $voyage,
            'offer' => $offer,
        ]);
    }


    #[Route('/offers', name: 'travel_offers', methods: ['GET'])]
    public function offers(): Response
    {
        $offers = $this->offerService->getActiveOffers();

        return $this->render('travel/offers.html.twig', [
            'active_nav' => 'offers',
            'offers' => $offers,
        ]);
    }

    #[Route('/bookings', name: 'travel_bookings', methods: ['GET'])]
    public function bookings(): Response
    {
        return $this->render('travel/bookings.html.twig', [
            'active_nav' => 'bookings',
        ]);
    }

    #[Route('/favorites', name: 'travel_favorites', methods: ['GET'])]
    public function favorites(): Response
    {
        return $this->render('travel/favorites.html.twig', [
            'active_nav' => 'favorites',
        ]);
    }

    #[Route('/contact', name: 'travel_contact', methods: ['GET'])]
    public function contact(): Response
    {
        return $this->render('travel/contact.html.twig', [
            'active_nav' => 'contact',
        ]);
    }

    #[Route('/favicon.ico', name: 'travel_favicon', methods: ['GET'])]
    public function favicon(): Response
    {
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}