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
        private NasaApiService         $nasaApiService,
        private PictureRepository      $pictureRepository,
        private EntityManagerInterface $entityManager,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Importation de l\'image du jour');

        $data = $this->nasaApiService->fetchNasaAPI();
        $status = $this->nasaApiService->persistPicture($data);

        switch ($status) {
            case NasaApiService::STATUS_PERSISTED:
                $io->success('L\'image du jour ' . $data['title'] . ' a été chargée dans la base de données');
                break;
            case NasaApiService::STATUS_ALREADY_EXISTS:
                $io->success('L\'image du jour ' . $data['title'] . ' est déjà chargée dans la base de données');
                break;
            case NasaApiService::STATUS_ERROR:
                $io->error("Une erreur est survenue lors de la persistance");
                return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
