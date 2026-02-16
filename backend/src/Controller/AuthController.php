<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class AuthController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // This controller is never actually executed.
        // The json_login authenticator intercepts the request before it reaches here.
        // It exists to register the route for OpenAPI/debug:router.
        return $this->json(['message' => 'Missing credentials.'], 401);
    }
}
