<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NasaController extends AbstractController
{
    #[Route('/', name: 'app_nasa')]
    public function index(): Response
    {
        return $this->render('nasa/index.html.twig', [
                'title' => 'Image du jour',
                'url' => 'https://static.nationalgeographic.fr/files/styles/image_3200/public/ouverture-espace-une-nouvelle-ere-de-decouvertes-ng289.webp',
                'explanation' => 'Description de l\'image',
            ]
        );
    }
}
