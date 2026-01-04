<?php

namespace App\Scheduler;

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DailyFetchHandler {
    public function __construct(private KernelInterface $kernel, private LoggerInterface $logger)
    {
    }

    public function __invoke(DailyFetchMessage $message): void
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'app:fetch-apod',
        ]);

        $output = new BufferedOutput();

        try {
            $application->run($input, $output);
            echo $output->fetch();
        } catch (Exception $e) {
            $this->logger->error("DailyFetchHandler erreur : " . $e->getMessage());
        }
    }
}