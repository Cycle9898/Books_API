<?php

namespace App\Controller;

use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[OA\Response(
        response: 200,
        description: "Got welcome message",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'int'),
                new OA\Property(property: 'message', type: 'string')
            ]
        )
    )]
    #[Route('/api', name: 'app_main', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return new JsonResponse([
            'status' => 200,
            'message' => 'Welcome to the Books API !'
        ]);
    }
}
