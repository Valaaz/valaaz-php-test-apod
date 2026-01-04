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

class NasaController extends AbstractController
{
    #[Route('/', name: 'app_nasa')]
    public function index(NasaApiService $nasaApiService, EntityManagerInterface $entityManager, PictureRepository $pictureRepository): Response
    {
        // Get data (API or Fallback)
        $data = $nasaApiService->fetchNasaAPI();

        try {
            $date = new DateTimeImmutable($data['date'] ?? 'now');
        } catch (Exception $e) {
            $date = new DateTimeImmutable('now');
            $this->addFlash('warning', 'Date de l\'API invalide, utilisation de la date du jour');
        }

        $existingPicture = $pictureRepository->findOneBy(['date' => $date]);

        if (!$existingPicture) {
            // Transform data to Entity
            $picture = $nasaApiService->createPictureFromAPI($data);

            // Persist
            $entityManager->persist($picture);
            $entityManager->flush();
        } else {
            $picture = $existingPicture;
            $this->addFlash('warning', 'Image déjà présente dans la base de données');
        }

        return $this->render('nasa/index.html.twig', [
                'picture' => $picture,
            ]
        );
    }
}
