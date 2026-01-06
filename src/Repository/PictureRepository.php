<?php

namespace App\Repository;

use App\Entity\Picture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Picture>
 */
class PictureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Picture::class);
    }

    /* Find the latest image that has the image mediaType */
    public function findLatestImage() : ?Picture {
        return $this->createQueryBuilder('p')
            ->andWhere('p.mediaType = :type')
            ->setParameter('type', 'image')
            ->orderBy('p.date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function persist(Picture $picture, bool $flush = false) : void {
        $this->getEntityManager()->persist($picture);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
