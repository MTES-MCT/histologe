<?php

namespace App\Command;

use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Repository\SignalementRepository;
use App\Service\HtmlCleaner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:fill-summary-signalement',
    description: 'Fill signalement summary',
)]
class FillSignalementSummaryCommand extends Command
{
    public const string API_ALBERT_URL = 'https://albert.api.etalab.gouv.fr/v1/chat/completions';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly SignalementRepository $signalementRepository,
        private readonly ParameterBagInterface $parameterBag,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $nbTrace = 0;
        for ($i = 40000; $i < 50000; $i++) {
            $signalement = $this->signalementRepository->find($i);
            if (!$signalement) {
                continue;
            }

            $messages = [];
            $messages []= $this->getMessage('Tu es un analyste de haute qualité.');
            $messages []= $this->getMessage('Ton travail est de résumer en français en quelques mots uniquement, le contenu d\'un email pour que n\'importe qui puisse savoir l\'essence de son propos.');
    
            $suivis = $signalement->getSuivis();
            if (\count($suivis) >= 15) {
                /** @var Suivi $suivi */
                foreach ($suivis as $suivi) {
                    if ($suivi->getType() !== Suivi::TYPE_AUTO && $suivi->getType() !== Suivi::TYPE_TECHNICAL) {
                        $suiviDescription = HtmlCleaner::clean($suivi->getDescription());
                        $messages []= $this->getMessage('Message du ' . $suivi->getCreatedAt()->format('d/m/Y') . ' : ' . $suiviDescription);
                    }
                }
        
                $payload = [
                    "messages" => $messages,
                    "model" => "AgentPublic/llama3-instruct-8b",
                    "stream" => false,
                    "n" => 1,
                ];
                $token = $this->parameterBag->get('albert_api_key');
                $options = [
                    'headers' => [
                        'Authorization: Bearer '.$token,
                        'Content-Type: application/json',
                    ],
                    'body' => json_encode($payload),
                ];
                $response = $this->httpClient->request('POST', self::API_ALBERT_URL, $options);
                $content = json_decode($response->getContent());

                $io->success($signalement->getUuid());
                $io->info($content->choices[0]->message->content);

                $nbTrace++;
                if ($nbTrace >= 10) {
                    break;
                }
            }
        }

        /*
        $progressBar = new ProgressBar($output, \count($epciList));
        $progressBar->start();
            $progressBar->advance();

        $progressBar->finish();
        $this->entityManager->flush();
        $io->success(\sprintf(
            'Signalement summary filled',
            $nbSignalements
        ));
        */

        return Command::SUCCESS;
    }

    private function initDataFromSignalement(Signalement $signalement, SymfonyStyle $io): void
    {
        $messages = [];
        $messages []= $this->getMessage('Tu es un analyste de haute qualité.');
        $messages []= $this->getMessage('Ton travail est de résumer en quelques mots uniquement, le contenu d\'un email pour que n\'importe qui puisse savoir l\'essence de son propos.');

        $suivis = $signalement->getSuivis();
        /** @var Suivi $suivi */
        foreach ($suivis as $suivi) {
            $suiviDescription = HtmlCleaner::clean($suivi->getDescription());
            $messages []= $this->getMessage('Message du ' . $suivi->getCreatedAt()->format('d/m/Y') . ' : ' . $suiviDescription);
        }

        $payload = [
            "messages" => $messages,
            "model" => "AgentPublic/llama3-instruct-8b",
            "stream" => false,
            "n" => 1,
        ];
        $token = $this->parameterBag->get('albert_api_key');
        $options = [
            'headers' => [
                'Authorization: Bearer '.$token,
                'Content-Type: application/json',
            ],
            'body' => json_encode($payload),
        ];
        $response = $this->httpClient->request('POST', self::API_ALBERT_URL, $options);
        $io->success($response->getStatusCode());
        $content = json_decode($response->getContent());
        dd($content->choices[0]->message->content);
    }

    private function getMessage(string $message): array
    {
        return ["role" => "user", "content" => $message];
    }
}
