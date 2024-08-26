<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use App\Service\VersioningService;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[Route('/api/books')]
class BookController extends AbstractController
{
    /**
     * This method is used to get all the books
     * 
     * @param BookRepository $bookRepo
     * @param SerializerInterface $serializer
     * @param Request $request
     * @param TagAwareCacheInterface $cache
     * @param VersioningService $versioningService
     * @return JsonResponse
     */
    #[OA\Response(
        response: 200,
        description: "Got books list",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type: Book::class))
        )
    )]
    #[OA\Parameter(
        name: "page",
        in: "query",
        description: "The number of results per page",
        schema: new OA\Schema(type: "int")
    )]
    #[OA\Parameter(
        name: "limit",
        in: "query",
        description: "The page number",
        schema: new OA\Schema(type: "int")
    )]
    #[OA\Response(
        response: 400,
        description: "When query parameters are invalid",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'int'),
                new OA\Property(property: 'message', type: 'string')
            ]
        )
    )]
    #[OA\Tag(name: "Books")]
    #[Route('', name: 'app_books', methods: ['GET'])]
    public function getBooksList(BookRepository $bookRepo, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cache, VersioningService $versioningService): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        if (!is_numeric($page) || !is_numeric($limit)) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, "Page and limit query parameters must be digits !");
        }

        $idCache = "getBooksList-" . $page . "-" . $limit;

        $jsonBooksList = $cache->get($idCache, function (ItemInterface $item) use ($serializer, $bookRepo, $page, $limit, $versioningService) {
            $item->tag("booksCache");

            $booksList = $bookRepo->findAllWithPagination($page, $limit);

            $version = $versioningService->getAPIVersion();

            $context = SerializationContext::create()->setVersion($version);
            return $serializer->serialize($booksList, 'json', $context);
        });

        return new JsonResponse($jsonBooksList, Response::HTTP_OK, [], true);
    }

    /**
     * This method is used to create a new book
     * 
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param UrlGeneratorInterface $urlGen
     * @param AuthorRepository $authorRepo
     * @param ValidatorInterface $validator
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[OA\Response(
        response: 201,
        description: "created a new book",
        content: new Model(type: Book::class)
    )]
    #[OA\RequestBody(content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'title', type: 'string'),
            new OA\Property(property: 'coverText', type: 'string'),
            new OA\Property(property: 'idAuthor', type: 'int'),
            new OA\Property(property: 'comment', type: 'string')
        ]
    ))]
    #[OA\Response(
        response: 400,
        description: "When data in request body are invalid",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'int'),
                new OA\Property(property: 'message', type: 'string')
            ]
        )
    )]
    #[OA\Tag(name: "Books")]
    #[Route('', name: 'app_book_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'You do not have sufficient rights to create a book')]
    public function createBook(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $manager,
        UrlGeneratorInterface $urlGen,
        AuthorRepository $authorRepo,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache
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
        $cache->invalidateTags(["booksCache"]);

        $jsonBook = $serializer->serialize($book, 'json');

        $location = $urlGen->generate('app_book_detail', ['id' => $book->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonBook, Response::HTTP_CREATED, ['location' => $location], true);
    }

    /**
     * This method is used to get a book details
     * 
     * @param Book $book
     * @param SerializerInterface $serializer
     * @param VersioningService $versioningService
     * @return JsonResponse
     */
    #[OA\Response(
        response: 200,
        description: "Get a book details",
        content: new Model(type: Book::class)
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID of the book',
        schema: new OA\Schema(type: 'int')
    )]
    #[OA\Tag(name: "Books")]
    #[Route('/{id}', name: 'app_book_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getDetailBook(Book $book, SerializerInterface $serializer, VersioningService $versioningService): JsonResponse
    {
        $version = $versioningService->getAPIVersion();

        $context = SerializationContext::create()->setVersion($version);
        $jsonBook = $serializer->serialize($book, 'json', $context);

        return new JsonResponse($jsonBook, Response::HTTP_OK, [], true);
    }

    /**
     * This method is used to edit a book
     * 
     * @param Request $request
     * @param Book $currentBook
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param AuthorRepository $authorRepo
     * @param ValidatorInterface $validator
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[OA\Response(
        response: 204,
        description: "edited a book",
        content: null
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID of the book',
        schema: new OA\Schema(type: 'int')
    )]
    #[OA\RequestBody(content: new OA\JsonContent(
        type: 'object',
        properties: [
            new OA\Property(property: 'title', type: 'string'),
            new OA\Property(property: 'coverText', type: 'string'),
            new OA\Property(property: 'idAuthor', type: 'int'),
            new OA\Property(property: 'comment', type: 'string')
        ]
    ))]
    #[OA\Response(
        response: 400,
        description: "When data in request body are invalid",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'int'),
                new OA\Property(property: 'message', type: 'string')
            ]
        )
    )]
    #[OA\Tag(name: "Books")]
    #[Route('/{id}', name: 'app_book_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'You do not have sufficient rights to modify a book')]
    public function updateBook(
        Request $request,
        Book $currentBook,
        SerializerInterface $serializer,
        EntityManagerInterface $manager,
        AuthorRepository $authorRepo,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $newBook = $serializer->deserialize($request->getContent(), Book::class, 'json');

        $currentBook->setTitle($newBook->getTitle());
        $currentBook->setCoverText($newBook->getCoverText());
        $currentBook->setComment($newBook->getComment());

        // check errors
        $errors = $validator->validate($currentBook);

        if ($errors->count() > 0) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, $errors[0]->getMessage());
        }

        // try to associate the book and the author from author id of the request
        $content = $request->toArray();
        $idAuthor = $content['idAuthor'] ?? -1;

        $currentBook->setAuthor($authorRepo->find($idAuthor));

        $manager->persist($currentBook);
        $manager->flush();

        // clear cache
        $cache->invalidateTags(["booksCache"]);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * This method is used to delete a book
     * 
     * @param Book $book
     * @param EntityManagerInterface $manager
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[OA\Response(
        response: 204,
        description: "deleted a book",
        content: null
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID of the book',
        schema: new OA\Schema(type: 'int')
    )]
    #[OA\Tag(name: "Books")]
    #[Route('/{id}', name: 'app_book_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'You do not have sufficient rights to delete a book')]
    public function deleteBook(Book $book, EntityManagerInterface $manager, TagAwareCacheInterface $cache): JsonResponse
    {
        $manager->remove($book);
        $manager->flush();

        // clear cache
        $cache->invalidateTags(["booksCache"]);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
