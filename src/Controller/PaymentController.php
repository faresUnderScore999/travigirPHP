<?php

namespace App\Controller;

use App\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaymentController extends AbstractController
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    /**
     * POST — Initiate a Flouci payment session for a reservation.
     * Protected by a per-reservation CSRF token.
     */
    #[Route('/payment/initiate/{id}', name: 'payment_initiate', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function initiate(Request $request, int $id): Response
    {
        if (!$this->isCsrfTokenValid('payment_initiate_' . $id, $request->request->get('_csrf_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $user = $request->getSession()->get('auth_user');
        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

        try {
            $paymentUrl = $this->paymentService->initiate(
                $id,
                $user['id'],
                $request->getClientIp() ?? 'unknown',
                $this->generateUrl('payment_success', ['id' => $id], UrlGeneratorInterface::ABSOLUTE_URL),
                $this->generateUrl('payment_fail',    ['id' => $id], UrlGeneratorInterface::ABSOLUTE_URL),
            );

            return $this->redirect($paymentUrl);
        } catch (\LogicException $e) {
            $this->addFlash('info', $e->getMessage());
        } catch (\Throwable $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('account_reservation_detail', ['id' => $id]);
    }

    /**
     * GET — Flouci redirects here on successful payment.
     * We verify the payment with Flouci before trusting it.
     */
    #[Route('/payment/success/{id}', name: 'payment_success', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function success(Request $request, int $id): Response
    {
        $user = $request->getSession()->get('auth_user');
        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

        $paymentId = (string) $request->query->get('payment_id', '');

        // Reject obviously malformed payment IDs before touching the DB or Flouci
        if (!$this->isValidPaymentId($paymentId)) {
            $this->addFlash('error', 'Invalid payment response received.');
            return $this->redirectToRoute('account_reservation_detail', ['id' => $id]);
        }

        try {
            if ($this->paymentService->verifyAndComplete($id, $user['id'], $paymentId)) {
                return $this->render('payment/success.html.twig', [
                    'reservation_id' => $id,
                    'payment_id'     => $paymentId,
                ]);
            }
        } catch (\Throwable) {
            // logged inside PaymentService
        }

        $this->addFlash('error', 'Payment could not be verified. Please contact support and provide reference: ' . htmlspecialchars($paymentId, ENT_QUOTES));
        return $this->redirectToRoute('account_reservation_detail', ['id' => $id]);
    }

    /**
     * GET — Flouci redirects here on failed or cancelled payment.
     */
    #[Route('/payment/fail/{id}', name: 'payment_fail', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function fail(Request $request, int $id): Response
    {
        $user = $request->getSession()->get('auth_user');
        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

        $paymentId = (string) $request->query->get('payment_id', '');
        if ($this->isValidPaymentId($paymentId)) {
            $this->paymentService->markFailed($id, $user['id'], $paymentId);
        }

        return $this->render('payment/fail.html.twig', [
            'reservation_id' => $id,
        ]);
    }

    /** Allow only safe alphanumeric strings (UUID-like) as payment IDs */
    private function isValidPaymentId(string $id): bool
    {
        return $id !== '' && (bool) preg_match('/^[a-zA-Z0-9\-_]{8,200}$/', $id);
    }
}
