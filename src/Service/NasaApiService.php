<?php

namespace App\Service;

use App\Entity\Picture;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NasaApiService {
    public function __construct(
        private HttpClientInterface $client,
        private string $nasaApiKey,
        private LoggerInterface $logger,
    ) {}

    /* Try to retrieve data from API, return raw data if exception */
    public function fetchNasaAPI(): array {
        try {
            $response = $this->client->request('GET', 'https://api.nasa.gov/planetary/apod' , [
                'query' => ['api_key' => $this->nasaApiKey,],
                'timeout' => 2.5,
            ]);

            return $response->toArray();
        } catch (TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|DecodingExceptionInterface $e) {
            $this->logger->error($e->getMessage());
            return $this->getFallbackData();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->getFallbackData();
        }
    }

    public function getFallbackData(): array {
        return [
            'title' => 'Simulation : Nébuleuse de la Tarentule',
            'date' => date('Y-m-d'),
            'explanation' => 'L\'API NASA est actuellement hors ligne. Voici une image de test.',
            'url' => 'https://static.nationalgeographic.fr/files/styles/image_3200/public/ouverture-espace-une-nouvelle-ere-de-decouvertes-ng289.webp',
            'media_type' => 'image',
            'is_fallback' => true,
        ];
    }

    /* Persist picture of the day in local db */
    public function createPictureFromAPI(array $data) : Picture {
        $picture = new Picture();
        $picture->setTitle($data['title']);
        $picture->setUrl($data['url']);

        try {
            $picture->setDate(new DateTimeImmutable($data['date']));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $picture->setDate(new DateTimeImmutable());
        }
        $picture->setExplanation($data['explanation']);
        $picture->setMediaType($data['media_type']);

        return $picture;
    }
}