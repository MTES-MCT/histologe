<?php

namespace App\Command;

use App\Entity\Territory;
use App\Manager\TerritoryManager;
use App\Service\Import\CsvParser;
use App\Service\Import\GridAffectation\GridAffectationLoader;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\UploadHandlerService;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:import-grid-affectation',
    description: 'Import grille affectation based on storage S3',
)]
class ImportGridAffectationCommand extends Command
{
    private const PARAM_TERRITORY_ZIP = 'territory_zip';
    private const PARAM_IGNORE_NOTIFICATION_PARTNER = 'ignore-notification-partners';

    public function __construct(
        private FilesystemOperator $fileStorage,
        private ParameterBagInterface $parameterBag,
        private CsvParser $csvParser,
        private TerritoryManager $territoryManager,
        private GridAffectationLoader $gridAffectationLoader,
        private UploadHandlerService $uploadHandlerService,
        private NotificationMailerRegistry $notificationMailerRegistry,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            self::PARAM_TERRITORY_ZIP,
            InputArgument::REQUIRED,
            'Territory zip to target'
        )
            ->addOption(
                self::PARAM_IGNORE_NOTIFICATION_PARTNER,
                null,
                InputOption::VALUE_REQUIRED,
                'Add partners types separated with comma'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $territoryZip = $input->getArgument(self::PARAM_TERRITORY_ZIP);
        $fromFile = 'csv/grille_affectation_'.$territoryZip.'.csv';
        $toFile = $this->parameterBag->get('uploads_tmp_dir').'grille.csv';

        /** @var Territory $territory */
        $territory = $this->territoryManager->findOneBy(['zip' => $territoryZip]);
        if (null === $territory) {
            $io->error('Territory does not exist');

            return Command::FAILURE;
        }

        if ($territory->isIsActive()) {
            $io->warning('Partner(s) and user(s) from this repository have already been added');

            return Command::FAILURE;
        }

        if (!$this->fileStorage->fileExists($fromFile)) {
            $io->error('CSV File does not exist');

            return Command::FAILURE;
        }

        $this->uploadHandlerService->createTmpFileFromBucket($fromFile, $toFile);

        $csvData = $this->csvParser->parseAsDict($toFile);
        $checkErrors = $this->gridAffectationLoader->validate(
            $csvData,
        );

        if (\count($checkErrors) > 0) {
            $io->error($checkErrors);

            return Command::FAILURE;
        }
        $io->success('No error detected in file');

        $ignoreNotifPartnerTypes = [];
        $ignoreNotifPartnerTypesParam = $input->getOption(self::PARAM_IGNORE_NOTIFICATION_PARTNER);
        if (null !== $ignoreNotifPartnerTypesParam) {
            $ignoreNotifPartnerTypes = explode(',', $ignoreNotifPartnerTypesParam);
        }

        $this->gridAffectationLoader->load(
            $territory,
            $csvData,
            $ignoreNotifPartnerTypes
        );

        $metadata = $this->gridAffectationLoader->getMetadata();
        $io->success($metadata['nb_partners'].' partner(s) created, '.$metadata['nb_users_created'].' user(s) created, '.$metadata['nb_users_updated'].' user(s) updated');

        $territory->setIsActive(true);
        $this->territoryManager->save($territory);
        $io->success($territory->getName().' has been activated');

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: $this->parameterBag->get('admin_email'),
                message: sprintf(
                    'Félicitation, le territoire %s est ouvert: %s partenaires, %s utilisateurs ont été crées et %s utilisateurs ont été mis à jour',
                    $territory->getName(),
                    $metadata['nb_partners'],
                    $metadata['nb_users_created'],
                    $metadata['nb_users_updated']
                ),
                cronLabel: 'Ouverture de territoire',
            )
        );

        return Command::SUCCESS;
    }
}
