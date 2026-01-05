<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SecurityController extends AbstractController
{
    public const SCOPES = [
        'google' => []
    ];

    #[Route('/login', name: 'auth_oauth_login')]
    public function login(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_apod');
        }

        return $this->render('auth/index.html.twig');
    }

    /**
     * @throws \Exception
     */
    #[Route('/logout', name: 'auth_oauth_logout')]
    public function logout(): never {
        throw new \Exception('logout');
    }

    #[Route('/oauth/connect/google', name: 'auth_oauth_connect')]
    public function connect(ClientRegistry $clientRegistry): RedirectResponse{
        return $clientRegistry->getClient('google')->redirect();
    }

    #[Route('/oauth/check/google', name: 'check')]
    public function check(): Response
    {
        return new Response(status: 200);
    }
}
