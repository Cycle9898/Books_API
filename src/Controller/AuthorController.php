<?php

namespace App\Controller;

use App\Entity\Author;
use App\Repository\AuthorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/v1/authors')]
class AuthorController extends AbstractController
{
    #[Route('', name: 'app_authors', methods: ['GET'])]
    public function getAuthorsList(AuthorRepository $authorRepo, SerializerInterface $serializer): JsonResponse
    {
        $authorsList = $authorRepo->findAll();
        $jsonAuthorsList = $serializer->serialize($authorsList, 'json', ['groups' => 'getAuthors']);

        return new JsonResponse($jsonAuthorsList, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'app_author_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getDetailAuthor(Author $author, SerializerInterface $serializer): JsonResponse
    {
        $jsonAuthor = $serializer->serialize($author, 'json', ['groups' => 'getAuthors']);

        return new JsonResponse($jsonAuthor, Response::HTTP_OK, [], true);
    }
}
