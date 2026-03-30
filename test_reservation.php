<?php

// Test script for reservation logic
// Run with: php test_reservation.php

require_once __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use App\Service\ReservationService;
use App\Service\VoyageService;
use App\Service\OfferService;
use App\Utility\DatabaseInitializer;

echo "Starting reservation logic test...\n";

try {
    // Boot Symfony kernel
    $kernel = new Kernel('dev', true);
    $kernel->boot();
    $container = $kernel->getContainer();

    echo "Kernel booted successfully.\n";

    // Get services
    $reservationService = $container->get(ReservationService::class);
    $voyageService = $container->get(VoyageService::class);
    $offerService = $container->get(OfferService::class);
    $databaseInitializer = $container->get(DatabaseInitializer::class);

    echo "Services loaded.\n";

    // Ensure database schema
    $databaseInitializer->ensureSchema();
    echo "Database schema ensured.\n";

    // Test data - adjust IDs as needed
    $userId = 1; // Assuming user ID 1 exists
    $voyageId = 1; // Assuming voyage ID 1 exists

    // Check if voyage exists
    $voyage = $voyageService->getVoyageById($voyageId);
    if (!$voyage) {
        echo "ERROR: Voyage ID $voyageId does not exist. Create some test data first.\n";
        exit(1);
    }
    echo "Voyage found: {$voyage['title']}\n";

    // Check for offers
    $offers = array_filter($offerService->getActiveOffers(), fn($o) => (int) $o['voyage_id'] === $voyageId);
    $activeOffer = $offers ? array_values($offers)[0] : null;
    echo "Active offer: " . ($activeOffer ? $activeOffer['title'] : 'None') . "\n";

    // Calculate price
    $numberOfPeople = 2;
    $voyagePrice = (float) ($voyage['price'] ?? 0);
    $discount = $activeOffer ? ((float) $activeOffer['discount_percentage'] / 100) : 0;
    $totalPrice = $numberOfPeople * $voyagePrice * (1 - $discount);

    echo "Price calculation: $numberOfPeople people * $$voyagePrice * (1 - $discount) = $$totalPrice\n";

    // Create reservation
    echo "Creating reservation...\n";
    $reservation = $reservationService->createReservation(
        $userId,
        $voyageId,
        $activeOffer ? (int) $activeOffer['id'] : null,
        $numberOfPeople,
        $totalPrice
    );

    if ($reservation) {
        echo "SUCCESS: Reservation created!\n";
        echo "ID: {$reservation['id']}\n";
        echo "Status: {$reservation['status']}\n";
        echo "Total Price: \${$reservation['total_price']}\n";
        echo "Date: {$reservation['reservation_date']}\n";

        // Test getting reservations for user
        $userReservations = $reservationService->getReservationsForUser($userId);
        echo "User has " . count($userReservations) . " reservations.\n";

        // Test admin list
        $allReservations = $reservationService->listAllReservations();
        echo "Total reservations in system: " . count($allReservations) . "\n";

        // Test confirm as admin
        if ($reservation['status'] === 'PENDING') {
            echo "Confirming reservation as admin...\n";
            $confirmed = $reservationService->confirmReservationAsAdmin($reservation['id']);
            if ($confirmed) {
                echo "SUCCESS: Reservation confirmed by admin.\n";
            } else {
                echo "ERROR: Failed to confirm reservation.\n";
            }
        }

    } else {
        echo "ERROR: Failed to create reservation.\n";
    }

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "Test completed.\n";