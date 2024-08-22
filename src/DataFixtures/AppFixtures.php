<?php

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {

        // creating 2 fake users. One with no privileges and one with admin privileges
        $basicUser = new User();
        $basicUser->setEmail('user@bookapi.com');
        $basicUser->setRoles(["ROLE_USER"]);
        $basicUser->setPassword($this->userPasswordHasher->hashPassword($basicUser, 'password123'));

        $manager->persist($basicUser);

        $adminUser = new User();
        $adminUser->setEmail('admin@bookapi.com');
        $adminUser->setRoles(["ROLE_ADMIN"]);
        $adminUser->setPassword($this->userPasswordHasher->hashPassword($adminUser, 'password123'));

        $manager->persist($adminUser);

        // creating 20 fake authors with basic first name and last name
        $authorsList = [];
        for ($i = 1; $i <= 20; $i++) {
            $author = new Author();
            $author->setFirstName("Prénom de l'auteur numéro $i");
            $author->setLastName("Nom de l'auteur numéro $i");
            $authorsList[] = $author;

            $manager->persist($author);
        }
        // creating 20 fake books with basic titles, cover texts and a random author
        for ($i = 1; $i <= 20; $i++) {
            $book = new Book();
            $book->setTitle("Titre du livre numéro $i");
            $book->setCoverText("Quatrième de couverture du livre numéro $i");
            $book->setComment("Commentaire du bibliothécaire numéro $i");
            $book->setAuthor($authorsList[array_rand($authorsList)]);

            $manager->persist($book);
        }

        $manager->flush();
    }
}
