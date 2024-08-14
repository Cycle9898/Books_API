<?php

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Book;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
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
            $book->setAuthor($authorsList[array_rand($authorsList)]);

            $manager->persist($book);
        }

        $manager->flush();
    }
}
