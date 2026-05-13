<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Controller\AdminController;
use App\Service\AuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\RefundRequest;
use App\Entity\User;

/**
 * Controller for admin management of refunds.
 */
#[Route('/admin')]
class AdminRefundController extends AbstractController
{
    public function __construct(
        private readonly AdminController $adminController,
        private readonly EntityManagerInterface $entityManager,
        private readonly AuthService $authService,
    ) {}

    /** List all refunded reservations */
 #[Route('/refunds', name: 'admin_refunds', methods: ['GET'])]
public function listRefunds(Request $request): Response
{
    if ($adminResp = $this->adminController->ensureIsAdmin($request)) {
        return $adminResp;
    }

    $refunds = $this->entityManager->getRepository(RefundRequest::class)->findAll();

    // Build an array mapping userId => email using AuthService
    $emailMap = [];
    foreach ($refunds as $refund) {
        $userId = $refund->getRequesterId();
        if (!isset($emailMap[$userId])) {
            $user = $this->authService->getUserById($userId);
            $emailMap[$userId] = $user ? $user['email'] : null;
        }
    }

    return $this->render('admin/refunds/list.html.twig', [
        'refunds' => $refunds,
        'emailMap' => $emailMap,
    ]);
}

    /** View and edit a single refund */
   #[Route('/refunds/{id}', name: 'admin_refund_detail', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
public function refundDetail(Request $request, int $id): Response
{
    if ($adminResp = $this->adminController->ensureIsAdmin($request)) {
        return $adminResp;
    }

    $refundRequest = $this->entityManager->getRepository(RefundRequest::class)->find($id);
    if (!$refundRequest) {
        throw $this->createNotFoundException('Refund request not found.');
    }

    // Fetch requester data using AuthService
    $requester = null;
    if ($refundRequest->getRequesterId()) {
        $requester = $this->authService->getUserById($refundRequest->getRequesterId());
    }

    if ($request->isMethod('POST')) {
        $status = $request->request->get('status');
        if ($status) {
            $refundRequest->setStatus($status);
            $this->entityManager->flush();
            $this->addFlash('success', 'Refund request updated.');
            return $this->redirectToRoute('admin_refund_detail', ['id' => $id]);
        }
    }

    return $this->render('admin/refunds/detail.html.twig', [
        'refund' => $refundRequest,
        'requester' => $requester,
    ]);
}
}
