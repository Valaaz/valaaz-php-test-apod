<?php

namespace App\Service;

use App\Entity\Picture;
use App\Repository\PictureRepository;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class NasaApiService
{
    public const STATUS_PERSISTED = 'persisted';
    public const STATUS_ALREADY_EXISTS = 'already_exists';
    public const STATUS_ERROR = 'error';

    private bool $isLastFetchFromBackup = false;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string              $nasaApiKey,
        private readonly LoggerInterface     $logger,
        private readonly PictureRepository   $pictureRepository
    )
    {
    }

    /* Try to retrieve data from APIs, return raw data if exception */
    public function fetchNasaAPI(): array
    {
        // Try NASA API
        try {
            $response = $this->client->request('GET', 'https://api.nasa.gov/planetary/apod', [
                'query' => ['api_key' => $this->nasaApiKey,],
                'timeout' => 2.5,
            ]);

            $this->isLastFetchFromBackup = false;

            return $response->toArray();
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage());

            // Try peapix API
            try {
                $response = $this->client->request('GET', 'https://peapix.com/bing/feed?country=fr&n=1');
                $this->isLastFetchFromBackup = true;

                $data = $response->toArray();
                return $data[0] ?? $data;
            } catch (Throwable $e) {
                // If all APIs fail, return raw data
                $this->logger->error($e->getMessage());
                $this->logger->error("Toutes les API ont échouées. Envoi des données brutes de secours.");
                $this->isLastFetchFromBackup = false; // False because my raw backup data have the same structure as NASA API
                return $this->getFallbackData();
            }
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

    /* Create picture object from backup API */
    private function createPictureFromBackupAPI(array $data): Picture
    {
        $picture = new Picture();
        $picture->setTitle($data['title']);
        $picture->setUrl($data['imageUrl']);

        try {
            $picture->setDate(new DateTimeImmutable($data['date']));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $picture->setDate(new DateTimeImmutable());
        }
        $picture->setExplanation('Aucune description fournie');
        $picture->setMediaType('image');

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
            if (!$this->isLastFetchFromBackup) {
                $picture = $this->createPictureFromAPI($data);
            } else {
                $picture = $this->createPictureFromBackupAPI($data);
            }

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