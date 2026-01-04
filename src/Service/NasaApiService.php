<?php

namespace App\Service;

use Symfony\Component\Yaml\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NasaApiService {
    public function __construct(
        private HttpClientInterface $client,
        private string $nasaApiKey,
    ) {}

    /* Try to retrieve data from API, return raw data if exception */
    public function fetchNasaAPI(): array {
        try {
            $response = $this->client->request('GET', 'https://api.nasa.gov/planetary/apod' , [
                'query' => ['api_key' => $this->nasaApiKey,],
                'timeout' => 2.5,
            ]);
            $statusCode = $response->getStatusCode();
            $contentType = $response->getHeaders()["Content-Type"][0];
            $content = $response->getContent();

            return $response->toArray();
        } catch (ExceptionInterface $exception) {
            return [
                'title' => 'Simulation : Nébuleuse de la Tarentule',
                'date' => date('Y-m-d'),
                'explanation' => 'L\'API NASA est actuellement hors ligne. Voici une image de test.',
                'url' => 'https://static.nationalgeographic.fr/files/styles/image_3200/public/ouverture-espace-une-nouvelle-ere-de-decouvertes-ng289.webp',
                'media_type' => 'image',
                'exception' => $exception->getMessage(),
            ];
        }
    }
}