<?php

namespace App\Controller;

use App\Entity\Author;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
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

#[Route('/api/v1/authors')]
class AuthorController extends AbstractController
{
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

    #[Route('/{id}', name: 'app_author_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getDetailAuthor(Author $author, SerializerInterface $serializer): JsonResponse
    {
        $jsonAuthor = $serializer->serialize($author, 'json');

        return new JsonResponse($jsonAuthor, Response::HTTP_OK, [], true);
    }

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
