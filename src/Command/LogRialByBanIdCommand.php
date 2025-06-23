<?php

namespace App\Command;

use App\Service\Gouv\Rial\RialService;
use App\Service\Import\CsvWriter;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(
    name: 'app:log-rial-by-ban-id',
    description: 'Fetches Rial data for a list of BAN ids and saves results to a CSV file',
)]
class LogRialByBanIdCommand extends Command
{
    public function __construct(
        private readonly RialService $rialService,
        private readonly ParameterBagInterface $parameterBag,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('banIds', InputArgument::REQUIRED, 'Comma-separated list of BAN ids');
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $banIdsArg = $input->getArgument('banIds');
        $banIds = array_map('trim', explode(',', $banIdsArg));
        $results = [];
        $io->progressStart(count($banIds));
        foreach ($banIds as $banId) {
            try {
                $identifiantsFiscaux = $this->rialService->searchLocauxByBanId($banId) ?? [];
                if (empty($identifiantsFiscaux)) {
                    $io->warning("No identifiants fiscaux found for BAN id: $banId");
                    $results[] = [
                        'ban_id' => $banId,
                        'identifiant_fiscal' => 'Aucun identifiant fiscal pour cet identifiant BAN',
                        'local_data' => '',
                    ];
                    $io->progressAdvance();
                    continue;
                }
                foreach ($identifiantsFiscaux as $identifiantFiscal) {
                    $localData = $this->rialService->searchLocalByIdFiscal($identifiantFiscal);
                    if ($localData) {
                        $results[] = [
                            'ban_id' => $banId,
                            'identifiant_fiscal' => $identifiantFiscal,
                            'local_data' => json_encode($localData),
                        ];
                    } else {
                        $io->warning("No local data for identifiant fiscal: $identifiantFiscal (BAN id: $banId)");
                        $results[] = [
                            'ban_id' => $banId,
                            'identifiant_fiscal' => $identifiantFiscal,
                            'local_data' => 'Aucune info pour cet identifiant fiscal',
                        ];
                    }
                }
            } catch (\Throwable $e) {
                $io->error("Error processing BAN id $banId: ".$e->getMessage());
                $results[] = [
                    'ban_id' => $banId,
                    'identifiant_fiscal' => 'ERROR BAN',
                    'local_data' => '',
                ];
            }
            $io->progressAdvance();
        }
        $io->progressFinish();
        // Write to CSV using CsvWriter
        $uploadsTmpDir = $this->parameterBag->get('uploads_tmp_dir');
        $timestamp = (new \DateTimeImmutable())->format('Ymd_His');
        $uuid = Uuid::v4();
        $csvFile = rtrim($uploadsTmpDir, '/')."/rial_results_{$timestamp}_{$uuid}.csv";
        $header = ['ban_id', 'identifiant_fiscal', 'local_data'];
        $csvWriter = new CsvWriter($csvFile, $header);
        foreach ($results as $row) {
            $csvWriter->writeRow([$row['ban_id'], $row['identifiant_fiscal'], $row['local_data']]);
        }
        $csvWriter->close();
        $io->success("Results written to $csvFile");

        // Send email to admin with file attached
        $adminEmail = $this->parameterBag->get('admin_email');
        $mailSent = $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_RIAL_EXPORT,
                to: $adminEmail,
                attachment: $csvFile,
            )
        );
        if ($mailSent) {
            $io->success('Admin notified by email with the file attached.');
        } else {
            $io->warning('Failed to send email to admin.');
        }

        return Command::SUCCESS;
    }
}
