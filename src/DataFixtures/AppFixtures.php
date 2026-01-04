<?php

namespace App\DataFixtures;

use App\Entity\Picture;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 5; $i++) {
            $picture = new Picture();
            $picture->setTitle("Image n°$i");
            $picture->setUrl("https://picsum.photos/seed/nasa$i/1024/768");
            $picture->setExplanation("Description de l'image de test numéro $i.");
            $date = new DateTimeImmutable("-$i days");
            $picture->setDate($date);
            $picture->setMediaType("image");

            $manager->persist($picture);
        }

        $manager->flush();
    }
}
