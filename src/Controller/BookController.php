<?php

namespace App\Controller;

use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/v1')]
class BookController extends AbstractController
{
    #[Route('/books', name: 'app_books', methods: ['GET'])]
    public function getBooksList(BookRepository $bookRepo, SerializerInterface $serializer): JsonResponse
    {
        $booksList = $bookRepo->findAll();
        $jsonBooksList = $serializer->serialize($booksList, 'json');

        return new JsonResponse($jsonBooksList, Response::HTTP_OK, [], true);
    }
}
