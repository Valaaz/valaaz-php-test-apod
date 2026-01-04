<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\PictureRepository;
use App\Service\NasaApiService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PictureController extends AbstractController
{
    #[Route('/', name: 'app_apod')]
    public function index(PictureRepository $pictureRepository): Response
    {
        $picture = $pictureRepository->findLatestImage();

        return $this->render('nasa/index.html.twig', [
                'picture' => $picture,
            ]
        );
    }
}
