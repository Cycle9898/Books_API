<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[Route('/api/v1/books')]
class BookController extends AbstractController
{
    #[Route('', name: 'app_books', methods: ['GET'])]
    public function getBooksList(BookRepository $bookRepo, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        if (!is_numeric($page) || !is_numeric($limit)) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, "Page and limit query parameters must be digits !");
        }

        $idCache = "getBooksList-" . $page . "-" . $limit;

        $jsonBooksList = $cachePool->get($idCache, function (ItemInterface $item) use ($serializer, $bookRepo, $page, $limit) {
            $item->tag("booksCache");

            $booksList = $bookRepo->findAllWithPagination($page, $limit);

            return $serializer->serialize($booksList, 'json', ['groups' => "getBooks"]);
        });

        return new JsonResponse($jsonBooksList, Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'app_book_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'You do not have sufficient rights to create a book')]
    public function createBook(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $manager,
        UrlGeneratorInterface $urlGen,
        AuthorRepository $authorRepo,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cachePool
    ): JsonResponse {
        $book = $serializer->deserialize($request->getContent(), Book::class, 'json');

        // check errors
        $errors = $validator->validate($book);

        if ($errors->count() > 0) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, $errors[0]->getMessage());
        }

        // try to associate the book and the author from author id of the request
        $content = $request->toArray();
        $idAuthor = $content['idAuthor'] ?? -1;

        $book->setAuthor($authorRepo->find($idAuthor));

        $manager->persist($book);
        $manager->flush();

        // clear cache
        $cachePool->invalidateTags(["booksCache"]);

        $jsonBook = $serializer->serialize($book, 'json', ['groups' => 'getBooks']);

        $location = $urlGen->generate('app_book_detail', ['id' => $book->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonBook, Response::HTTP_CREATED, ['location' => $location], true);
    }

    #[Route('/{id}', name: 'app_book_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getDetailBook(Book $book, SerializerInterface $serializer): JsonResponse
    {
        $jsonBook = $serializer->serialize($book, 'json', ['groups' => "getBooks"]);

        return new JsonResponse($jsonBook, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'app_book_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'You do not have sufficient rights to modify a book')]
    public function updateBook(
        Request $request,
        Book $currentBook,
        SerializerInterface $serializer,
        EntityManagerInterface $manager,
        AuthorRepository $authorRepo,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cachePool
    ): JsonResponse {
        $updatedBook = $serializer->deserialize($request->getContent(), Book::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentBook]);

        // check errors
        $errors = $validator->validate($updatedBook);

        if ($errors->count() > 0) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, $errors[0]->getMessage());
        }

        // try to associate the book and the author from author id of the request
        $content = $request->toArray();
        $idAuthor = $content['idAuthor'] ?? -1;

        $updatedBook->setAuthor($authorRepo->find($idAuthor));

        $manager->persist($updatedBook);
        $manager->flush();

        // clear cache
        $cachePool->invalidateTags(["booksCache"]);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}', name: 'app_book_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'You do not have sufficient rights to delete a book')]
    public function deleteBook(Book $book, EntityManagerInterface $manager, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $manager->remove($book);
        $manager->flush();

        // clear cache
        $cachePool->invalidateTags(["booksCache"]);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
