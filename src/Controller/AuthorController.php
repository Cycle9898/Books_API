<?php

namespace App\Controller;

use App\Entity\Author;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
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

#[Route('/api/authors')]
class AuthorController extends AbstractController
{
    /**
     * This method is used to get all the authors
     * 
     * @param AuthorRepository $authorRepo
     * @param SerializerInterface $serializer
     * @param Request $request
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[OA\Response(
        response: 200,
        description: "Got authors list",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type: Author::class))
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
    #[OA\Tag(name: "Authors")]
    #[Route('', name: 'app_authors', methods: ['GET'])]
    public function getAuthorsList(AuthorRepository $authorRepo, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cache): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        if (!is_numeric($page) || !is_numeric($limit)) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, "Page and limit query parameters must be digits !");
        }

        $idCache = "getAuthorsList-" . $page . "-" . $limit;

        $jsonAuthorsList = $cache->get($idCache, function (ItemInterface $item) use ($serializer, $authorRepo, $page, $limit) {
            $item->tag("authorsCache");

            $authorsList = $authorRepo->findAllWithPagination($page, $limit);

            return $serializer->serialize($authorsList, 'json');
        });

        return new JsonResponse($jsonAuthorsList, Response::HTTP_OK, [], true);
    }

    /**
     * This method is used to create a new author
     * 
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param UrlGeneratorInterface $urlGen
     * @param ValidatorInterface $validator
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[OA\Response(
        response: 201,
        description: "created a new author",
        content: new Model(type: Author::class)
    )]
    #[OA\RequestBody(content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'firstName', type: 'string'),
            new OA\Property(property: 'lastName', type: 'string')
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
    #[OA\Tag(name: "Authors")]
    #[Route('', name: 'app_author_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'You do not have sufficient rights to create an author')]
    public function createAuthor(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $manager,
        UrlGeneratorInterface $urlGen,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $author = $serializer->deserialize($request->getContent(), Author::class, 'json');

        // check errors
        $errors = $validator->validate($author);

        if ($errors->count() > 0) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, $errors[0]->getMessage());
        }

        $manager->persist($author);
        $manager->flush();

        // clear cache
        $cache->invalidateTags(["authorsCache"]);

        $jsonAuthor = $serializer->serialize($author, 'json');

        $location = $urlGen->generate('app_author_detail', ['id' => $author->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonAuthor, Response::HTTP_CREATED, ['location' => $location], true);
    }

    /**
     * This method is used to get an author details
     * 
     * @param Author $author
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[OA\Response(
        response: 200,
        description: "Got an authors details",
        content: new Model(type: Author::class)
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID of the author',
        schema: new OA\Schema(type: 'int')
    )]
    #[OA\Tag(name: "Authors")]
    #[Route('/{id}', name: 'app_author_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getDetailAuthor(Author $author, SerializerInterface $serializer): JsonResponse
    {
        $jsonAuthor = $serializer->serialize($author, 'json');

        return new JsonResponse($jsonAuthor, Response::HTTP_OK, [], true);
    }

    /**
     * This method is used to edit an author
     * 
     * @param Request $request
     * @param Author $currentAuthor
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $manager
     * @param ValidatorInterface $validator
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[OA\Response(
        response: 204,
        description: "edited an author",
        content: null
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID of the author',
        schema: new OA\Schema(type: 'int')
    )]
    #[OA\RequestBody(content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'firstName', type: 'string'),
            new OA\Property(property: 'lastName', type: 'string')
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
    #[OA\Tag(name: "Authors")]
    #[Route('/{id}', name: 'app_author_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'You do not have sufficient rights to modify an author')]
    public function updateAuthor(
        Request $request,
        Author $currentAuthor,
        SerializerInterface $serializer,
        EntityManagerInterface $manager,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $newAuthor = $serializer->deserialize($request->getContent(), Author::class, 'json');

        $currentAuthor->setFirstName($newAuthor->getFirstName());
        $currentAuthor->setLastName($newAuthor->getLastName());

        // check errors
        $errors = $validator->validate($currentAuthor);

        if ($errors->count() > 0) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, $errors[0]->getMessage());
        }

        $manager->persist($currentAuthor);
        $manager->flush();

        // clear cache
        $cache->invalidateTags(["authorsCache"]);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * This method is used to delete an author
     * 
     * @param Author $author
     * @param EntityManagerInterface $manager
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[OA\Response(
        response: 204,
        description: "deleted an author",
        content: null
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID of the author',
        schema: new OA\Schema(type: 'int')
    )]
    #[OA\Tag(name: "Authors")]
    #[Route('/{id}', name: 'app_author_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'You do not have sufficient rights to delete an author')]
    public function deleteAuthor(Author $author, EntityManagerInterface $manager, TagAwareCacheInterface $cache): JsonResponse
    {
        $manager->remove($author);
        $manager->flush();

        // clear cache
        $cache->invalidateTags(["authorsCache"]);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
