<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SymfonyDocsController extends AbstractController
{
    /**
     * This methods make an API call to 'https://api.github.com/repos/symfony/symfony-docs'
     * and return symfony Docs in JSON format
     * 
     * @param HttpClientInterface $httpClient
     * @return JsonResponse
     */
    #[Route('/api/external/sf-docs', name: 'app_external_symfony-docs', methods: ['GET'])]
    public function getSymfonyDocs(HttpClientInterface $httpClient): JsonResponse
    {
        $response = $httpClient->request(
            'GET',
            'https://api.github.com/repos/symfony/symfony-docs'
        );

        return new JsonResponse($response->getContent(), $response->getStatusCode(), [], true);
    }
}
