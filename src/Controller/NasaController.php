<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\NasaApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NasaController extends AbstractController
{
    #[Route('/', name: 'app_nasa')]
    public function index(NasaApiService $nasaApiService): Response
    {
        $data = $nasaApiService->fetchNasaAPI();

        return $this->render('nasa/index.html.twig', [
                'title' => $data['title'],
                'url' => $data['url'],
                'explanation' => $data['explanation'],
            ]
        );
    }
}
