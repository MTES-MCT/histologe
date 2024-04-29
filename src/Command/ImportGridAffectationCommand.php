<?php

namespace App\Command;

use App\Entity\Enum\PartnerType;
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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsCommand(
    name: 'app:import-grid-affectation',
    description: 'Import grille affectation based on storage S3',
)]
class ImportGridAffectationCommand extends Command
{
    private const PARAM_TERRITORY_ZIP = 'territory_zip';
    private const PARAM_IGNORE_NOTIFICATION_ALL_PARTNER = 'ignore-notification-all';
    private const PARAM_IGNORE_NOTIFICATION_PARTNER = 'ignore-notification-partners';
    private const PARAM_FILE_VERSION = 'file-version';

    public function __construct(
        private FilesystemOperator $fileStorage,
        private ParameterBagInterface $parameterBag,
        private CsvParser $csvParser,
        private TerritoryManager $territoryManager,
        private GridAffectationLoader $gridAffectationLoader,
        private UploadHandlerService $uploadHandlerService,
        private NotificationMailerRegistry $notificationMailerRegistry,
        private UrlGeneratorInterface $urlGenerator,
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
            )
            ->addOption(
                self::PARAM_IGNORE_NOTIFICATION_ALL_PARTNER,
                null,
                null,
                'Partners will not be notify'
            )
            ->addOption(self::PARAM_FILE_VERSION, null, InputOption::VALUE_REQUIRED, 'Grid affectation file version')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $territoryZip = $input->getArgument(self::PARAM_TERRITORY_ZIP);
        $fromFile = 'csv/grille_affectation_'.$territoryZip.'.csv';
        $toFile = $this->parameterBag->get('uploads_tmp_dir').'grille.csv';
        $isModeUpdate = false;
        $fileVersion = $input->getOption(self::PARAM_FILE_VERSION);

        /** @var Territory $territory */
        $territory = $this->territoryManager->findOneBy(['zip' => $territoryZip]);

        if ($fileVersion) {
            $fromFile = 'csv/grille_affectation_'.$territoryZip.'-'.$fileVersion.'.csv';
            $isModeUpdate = true;
        }

        if (!$this->canExecute($io, $isModeUpdate, $fromFile, $territory)) {
            return Command::FAILURE;
        }

        $this->uploadHandlerService->createTmpFileFromBucket($fromFile, $toFile);

        $csvData = $this->csvParser->parseAsDict($toFile);
        $checkErrors = $this->gridAffectationLoader->validate(
            $csvData,
            $isModeUpdate
        );

        if (\count($checkErrors) > 0) {
            $io->error($checkErrors);

            return Command::FAILURE;
        }
        $io->success('No error detected in file');

        $ignoreNotifPartnerTypes = [];
        $ignoreNotifPartnerTypesParam = $input->getOption(self::PARAM_IGNORE_NOTIFICATION_PARTNER);
        $ignoredNotifAllPartners = $input->getOption(self::PARAM_IGNORE_NOTIFICATION_ALL_PARTNER);
        if (null !== $ignoreNotifPartnerTypesParam) {
            $ignoreNotifPartnerTypes = explode(',', $ignoreNotifPartnerTypesParam);
        } elseif ($ignoredNotifAllPartners) {
            $ignoreNotifPartnerTypes = array_keys(PartnerType::getLabelList());
        }

        $this->gridAffectationLoader->load(
            $territory,
            $csvData,
            $ignoreNotifPartnerTypes,
            $output
        );

        $metadata = $this->gridAffectationLoader->getMetadata();
        foreach ($metadata['errors'] as $error) {
            $io->warning($error);
        }

        $io->success(sprintf('%d partner(s) created, %d user(s) created',
            $metadata['nb_partners'],
            $metadata['nb_users_created']
        ));

        if (0 === $metadata['nb_partners'] && 0 === $metadata['nb_users_created']) {
            return Command::FAILURE;
        }

        if ($isModeUpdate) {
            $message = 'Bravo, le territoire %s a été mis à jour : %s partenaires, %s utilisateurs ont été crées';
            $ioSuccessMessage = $territory->getName().' has been updated';
        } else {
            $message = 'Bravo, le territoire %s est ouvert: %s partenaires, %s utilisateurs ont été crées';
            $ioSuccessMessage = $territory->getName().' has been activated';
            $territory->setIsActive(true);
            $this->territoryManager->save($territory);
        }

        $io->success($ioSuccessMessage);

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: $this->parameterBag->get('admin_email'),
                message: sprintf(
                    $message,
                    $territory->getName(),
                    $metadata['nb_partners'],
                    $metadata['nb_users_created']
                ),
                cronLabel: 'Ouverture de territoire',
            )
        );

        $partnerLink = $this->urlGenerator->generate('back_partner_index', [
            'territory' => $territory->getId(),
            'type' => PartnerType::ARS->value, ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $io->warning(
            sprintf('[Esabora] Merci de saisir les identifiants des nouveaux partenaires %s en cliquant sur le lien %s',
                PartnerType::ARS->value,
                \PHP_EOL.$partnerLink
            )
        );

        return Command::SUCCESS;
    }

    private function canExecute(
        SymfonyStyle $io,
        bool $isModeUpdate,
        string $fromFile,
        ?Territory $territory = null
    ): bool {
        $canExecute = true;

        if (null === $territory) {
            $io->error('Territory does not exist');

            $canExecute = false;
        } elseif (($isModeUpdate && !$territory->isIsActive()) || (!$isModeUpdate && $territory->isIsActive())) {
            $io->warning($isModeUpdate
                ? 'The --'.self::PARAM_FILE_VERSION.' option cannot be applied on an inactive territory. Please remove it.'
                : 'Partner(s) and user(s) from this repository have already been added'
            );

            $canExecute = false;
        } elseif (!$this->fileStorage->fileExists($fromFile)) {
            $io->error('File '.$fromFile.' does not exist');

            $canExecute = false;
        }

        return $canExecute;
    }
}
