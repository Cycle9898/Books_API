<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/v1/books')]
class BookController extends AbstractController
{
    #[Route('', name: 'app_books', methods: ['GET'])]
    public function getBooksList(BookRepository $bookRepo, SerializerInterface $serializer): JsonResponse
    {
        $booksList = $bookRepo->findAll();
        $jsonBooksList = $serializer->serialize($booksList, 'json', ['groups' => "getBooks"]);

        return new JsonResponse($jsonBooksList, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'app_book_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getDetailBook(Book $book, SerializerInterface $serializer): JsonResponse
    {
        $jsonBook = $serializer->serialize($book, 'json', ['groups' => "getBooks"]);

        return new JsonResponse($jsonBook, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'app_book_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function deleteBook(Book $book, EntityManagerInterface $manager): JsonResponse
    {
        $manager->remove($book);
        $manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
