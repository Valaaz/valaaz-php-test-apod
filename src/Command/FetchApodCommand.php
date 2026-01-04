<?php

namespace App\Command;

use App\Repository\PictureRepository;
use App\Service\NasaApiService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fetch-apod',
    description: 'Fetch APOD from Nasa API',
)]
class FetchApodCommand extends Command
{
    public function __construct(
        private NasaApiService $nasaApiService,
        private PictureRepository $pictureRepository,
        private EntityManagerInterface $entityManager,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Importation de l\'image du jour');

        // Get data (API or Fallback)
        $data = $this->nasaApiService->fetchNasaAPI();

        try {
            $date = new DateTimeImmutable($data['date'] ?? 'now');
        } catch (Exception $e) {
            $date = new DateTimeImmutable('now');
        }

        $existingPicture = $this->pictureRepository->findOneBy(['date' => $date]);

        if (!$existingPicture) {
            // Transform data to Entity
            $picture = $this->nasaApiService->createPictureFromAPI($data);

            // Persist
            $this->entityManager->persist($picture);
            $this->entityManager->flush();

            $io->success('L\'image du jour ' . $picture->getTitle() . ' a été chargée dans la base de données');
        } else {
            $io->success('L\'image du jour ' . $existingPicture->getTitle() . ' est déjà chargée dans la base de données');
        }


        return Command::SUCCESS;
    }
}
