<?php

namespace App\Controller;

use App\Entity\Author;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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

    #[Route('', name: 'app_author_create', methods: ['POST'])]
    public function createAuthor(Request $request, SerializerInterface $serializer, EntityManagerInterface $manager, UrlGeneratorInterface $urlGen): JsonResponse
    {
        $author = $serializer->deserialize($request->getContent(), Author::class, 'json');

        $manager->persist($author);
        $manager->flush();

        $jsonAuthor = $serializer->serialize($author, 'json', ['groups' => 'getAuthors']);

        $location = $urlGen->generate('app_author_detail', ['id' => $author->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonAuthor, Response::HTTP_CREATED, ['location' => $location], true);
    }

    #[Route('/{id}', name: 'app_author_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getDetailAuthor(Author $author, SerializerInterface $serializer): JsonResponse
    {
        $jsonAuthor = $serializer->serialize($author, 'json', ['groups' => 'getAuthors']);

        return new JsonResponse($jsonAuthor, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'app_author_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function deleteAuthor(Author $author, EntityManagerInterface $manager): JsonResponse
    {
        $manager->remove($author);
        $manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
