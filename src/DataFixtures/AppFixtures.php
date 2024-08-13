<?php

namespace App\DataFixtures;

use App\Entity\Book;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // creating 20 fake books with basic titles and cover texts
        for ($i = 1; $i <= 20; $i++) {
            $book = new Book();
            $book->setTitle("Titre du livre numéro $i");
            $book->setCoverText("Quatrième de couverture du livre numéro $i");

            $manager->persist($book);
        }

        $manager->flush();
    }
}
