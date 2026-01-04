<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ApodController extends AbstractController
{
    #[Route('/apod', name: 'apod')]
    public function index(): Response
    {
        return $this->render('apod/index.html.twig');
    }
}
