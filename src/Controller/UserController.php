<?php

namespace App\Controller;

use App\Service\AuthService;
use App\Service\VoyageService;
use App\Service\ValidationService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly VoyageService $voyageService,
        private readonly ValidationService $validationService,
        private readonly LoggerInterface $logger
    ) {
    }

    private function ensureAuthenticated(Request $request): ?Response
    {
        $user = $request->getSession()->get('auth_user');
        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

        return null;
    }

    #[Route('/account/settings', name: 'account_settings', methods: ['GET'])]
    public function getSettings(Request $request): Response
    {
        $session = $request->getSession();
        $authUser = $session->get('auth_user');

        if (!$authUser || !isset($authUser['id'])) {
            return $this->redirectToRoute('auth_login');
        }

        $formData = [
            'username' => $authUser['username'] ?? '',
            'email' => $authUser['email'] ?? '',
            'tel' => $authUser['tel'] ?? '',
            'image_url' => $authUser['image_url'] ?? '',
        ];

        return $this->render('auth/settings.html.twig', [
            'active_nav' => 'account',
            'formData' => $formData,
            'error' => null,
            'success' => null,
        ]);
    }

    #[Route('/account/settings', name: 'account_settings_update', methods: ['POST'])]
    public function updateSettings(Request $request): Response
    {
        $session = $request->getSession();
        $authUser = $session->get('auth_user');

        if (!$authUser || !isset($authUser['id'])) {
            return $this->redirectToRoute('auth_login');
        }

        $error = null;
        $success = null;
        $formData = [
            'username' => $authUser['username'] ?? '',
            'email' => $authUser['email'] ?? '',
            'tel' => $authUser['tel'] ?? '',
            'image_url' => $authUser['image_url'] ?? '',
        ];

        $formData['username'] = (string) $request->request->get('username', '');
        $formData['email'] = (string) $request->request->get('email', '');
        $formData['tel'] = (string) $request->request->get('tel', '');
        $formData['image_url'] = (string) $request->request->get('image_url', '');
        $currentPassword = (string) $request->request->get('current_password', '');
        $newPassword = (string) $request->request->get('new_password', '');
        $confirmPassword = (string) $request->request->get('confirm_password', '');

        $this->validationService->clearErrors();
        $this->validationService->validateRequired($formData, ['username', 'email']);
        $this->validationService->validateEmail($formData['email']);
        $this->validationService->validateString($formData['username'], 'username', 3, 50);
        $this->validationService->validateAlphaNum($formData['username'], 'username');

        if (!empty($formData['tel'])) {
            $this->validationService->validatePhone($formData['tel']);
        }

        if ($newPassword !== '') {
            $this->validationService->validateString($newPassword, 'new_password', 6);
            if ($newPassword !== $confirmPassword) {
                $this->validationService->getErrors()['confirm_password'][] = 'New password confirmation does not match.';
            }
            if ($currentPassword === '') {
                $this->validationService->getErrors()['current_password'][] = 'You must enter your current password to change password.';
            }
        }

        if (!$this->validationService->isValid()) {
            $errors = $this->validationService->getErrors();
            $error = implode(' ', array_map(fn($e) => implode(', ', $e), $errors));
        } elseif ($currentPassword !== '' && !$this->authService->checkPasswordForUser($authUser['id'], $currentPassword)) {
            $error = 'Current password is incorrect.';
        } else {
            $updated = $this->authService->updateProfile(
                $authUser['id'],
                $formData['username'],
                $formData['email'],
                $formData['tel'],
                $formData['image_url'],
                $newPassword !== '' ? $newPassword : null,
                $currentPassword !== '' ? $currentPassword : null
            );

            if ($updated === null) {
                $error = 'Unable to save changes. Email may be in use, or validation failed.';
            } else {
                $success = 'Account settings updated successfully.';
                $updated['is_admin'] = $this->authService->isAdmin($authUser['id']);
                $session->set('auth_user', $updated);
            }
        }

        return $this->render('auth/settings.html.twig', [
            'active_nav' => 'account',
            'formData' => $formData,
            'error' => $error,
            'success' => $success,
        ]);
    }

    #[Route('/account/delete', name: 'account_delete', methods: ['POST'])]
    public function deleteAccount(Request $request): Response
    {
        $session = $request->getSession();
        $authUser = $session->get('auth_user');

        if (!$authUser || !isset($authUser['id'])) {
            return $this->redirectToRoute('auth_login');
        }

        $userId = $authUser['id'];
        $deleted = $this->authService->deleteUser($userId);

        if ($deleted) {
            $session->clear();
            $this->addFlash('success', 'Your account has been deleted.');
        } else {
            $this->addFlash('error', 'Unable to delete account. Please try again.');
            return $this->redirectToRoute('account_settings');
        }

        return $this->redirectToRoute('travel_home');
    }

    #[Route('/register', name: 'auth_register', methods: ['GET', 'POST'])]
    public function register(Request $request): Response
    {
        if ($request->getSession()->has('auth_user')) {
            return $this->redirectToRoute('travel_home');
        }

        $error = null;
        $username = '';
        $email = '';

        if ($request->isMethod('POST')) {
            $username = (string) $request->request->get('username', '');
            $email = (string) $request->request->get('email', '');
            $password = (string) $request->request->get('password', '');
            $confirmPassword = (string) $request->request->get('confirm_password', '');

            $this->validationService->validateUserRegistration([
                'username' => $username,
                'email' => $email,
                'password' => $password,
            ]);

            if ($password !== $confirmPassword) {
                $this->validationService->getErrors()['confirm_password'][] = 'Passwords do not match.';
            }

            if (!$this->validationService->isValid()) {
                $errors = $this->validationService->getErrors();
                $error = implode(' ', array_map(fn($e) => implode(', ', $e), $errors));
            } else {
                try {
                    $user = $this->authService->register($username, $email, $password);
                    if ($user !== null) {
                        $request->getSession()->set('auth_user', $user);
                        return $this->redirectToRoute('travel_home');
                    }
                    $error = 'Email already exists.';
                } catch (\Throwable $e) {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }

        return $this->render('auth/register.html.twig', [
            'active_nav' => '',
            'username' => $username,
            'email' => $email,
            'error' => $error,
        ]);
    }

    #[Route('/account/favorites', name: 'account_favorites', methods: ['GET'])]
    public function accountFavorites(Request $request): Response
    {
        if ($this->ensureAuthenticated($request) !== null) {
            return $this->redirectToRoute('auth_login');
        }

        $voyages = $this->voyageService->getFeaturedVoyages(3);

        return $this->render('travel/favorites.html.twig', [
            'active_nav' => 'account',
            'favorites' => $voyages,
        ]);
    }
}