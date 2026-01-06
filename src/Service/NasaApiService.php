<?php

namespace App\Service;

use App\Entity\Picture;
use App\Repository\PictureRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NasaApiService
{
    public const STATUS_PERSISTED = 'persisted';
    public const STATUS_ALREADY_EXISTS = 'already_exists';
    public const STATUS_ERROR = 'error';

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string              $nasaApiKey,
        private readonly LoggerInterface     $logger,
        private readonly PictureRepository $pictureRepository
    )
    {
    }

    /* Try to retrieve data from API, return raw data if exception */
    public function fetchNasaAPI(): array
    {
        try {
            $response = $this->client->request('GET', 'https://api.nasa.gov/planetary/apod', [
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

    public function getFallbackData(): array
    {
        return [
            'title' => 'Simulation : Nébuleuse de la Tarentule',
            'date' => date('Y-m-d'),
            'explanation' => 'L\'API NASA est actuellement hors ligne. Voici une image de test.',
            'url' => 'https://static.nationalgeographic.fr/files/styles/image_3200/public/ouverture-espace-une-nouvelle-ere-de-decouvertes-ng289.webp',
            'media_type' => 'image',
            'is_fallback' => true,
        ];
    }

    /* Create picture object */
    private function createPictureFromAPI(array $data): Picture
    {
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

    /* Persist a picture to database */
    public function persistPicture(array $data): string
    {
        try {
            $date = $this->safeParseDate($data['date']);

            $existingPicture = $this->pictureRepository->findOneBy(['date' => $date]);
            if ($existingPicture) {
                $this->logger->info("L'image du jour " . $date->format('d/m/y') . " est déjà présente dans la base de données");
                return self::STATUS_ALREADY_EXISTS;
            }

            // Transform data to Entity
            $picture = $this->createPictureFromAPI($data);

            // Persist
            $this->pictureRepository->persist($picture, true);

            return self::STATUS_PERSISTED;
        } catch (Exception $e) {
            $this->logger->error("Erreur de persistance : " . $e->getMessage());
            return self::STATUS_ERROR;
        }
    }

    private function safeParseDate(?string $dateString): DateTimeImmutable
    {
        try {
            return $dateString ? new DateTimeImmutable($dateString) : new DateTimeImmutable();
        } catch (Exception $e) {
            $this->logger->error("Date invalide reçue : $dateString");
            return new DateTimeImmutable();
        }
    }
}