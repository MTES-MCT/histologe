<?php

namespace App\Command\Cron;

use App\Entity\Enum\InterfacageType;
use App\Entity\Enum\PartnerType;
use App\Messenger\InterconnectionBus;
use App\Messenger\Message\Esabora\DossierMessageSCHS;
use App\Repository\AffectationRepository;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\Interconnection\Idoss\IdossService;
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
use Symfony\Component\Messenger\Exception\ExceptionInterface;

#[AsCommand(
    name: 'app:retry-failed-push',
    description: 'Retry failed push dossier si-sh, schs, idoss'
)]
class RetryFailedPushCommand extends AbstractCronCommand
{
    private const array MAPPING_SERVICES = [
        'sish' => [
            'label' => 'SI-SH',
            'interfacageType' => InterfacageType::ESABORA,
            'action' => AbstractEsaboraService::ACTION_PUSH_DOSSIER_ADRESSE,
            'partnerTypes' => [PartnerType::ARS],
        ],
        'schs' => [
            'label' => 'Esabora SCHS',
            'interfacageType' => InterfacageType::ESABORA,
            'action' => AbstractEsaboraService::ACTION_PUSH_DOSSIER,
            'partnerTypes' => DossierMessageSCHS::CAN_SYNC_SCHS_ESABORA,
        ],
        'idoss' => [
            'label' => 'IDOSS',
            'interfacageType' => InterfacageType::IDOSS,
            'action' => IdossService::ACTION_PUSH_DOSSIER,
            'partnerTypes' => [],
        ],
    ];

    public function __construct(
        private readonly AffectationRepository $affectationRepository,
        private readonly InterconnectionBus $interconnectionBus,
        private readonly ParameterBagInterface $parameterBag,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
    ) {
        parent::__construct($this->parameterBag);
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'service_type',
                InputArgument::REQUIRED,
                sprintf(
                    'Type de service externe (%s)',
                    implode(', ', array_keys(self::MAPPING_SERVICES))
                )
            );
    }

    /**
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $serviceType = (string) $input->getArgument('service_type');

        if (!isset(self::MAPPING_SERVICES[$serviceType])) {
            $io->error(sprintf(
                'Type de service "%s" inconnu. Valeurs possibles : %s',
                $serviceType,
                implode(', ', array_keys(self::MAPPING_SERVICES))
            ));

            return Command::INVALID;
        }

        $config = self::MAPPING_SERVICES[$serviceType];

        $affectations = $this->affectationRepository->findAffectationsWithFailedJobEvents(
            $config['interfacageType']->value,
            $config['action'],
            $config['partnerTypes'],
        );

        foreach ($affectations as $affectation) {
            $this->interconnectionBus->dispatch($affectation);
            $io->success(\sprintf(
                '[%s] Dossier %s pushed to %s',
                $affectation->getPartner()->getType()->value,
                $affectation->getSignalement()->getUuid(),
                $config['label']
            ));
        }

        $nbAffectations = \count($affectations);
        $io->success(\sprintf('%s dossier(s) has been pushed', $nbAffectations));

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: (string) $this->parameterBag->get('admin_email'),
                message: \sprintf('%s dossier(s) ont été repoussé vers %s', $nbAffectations, $config['label']),
                cronLabel: sprintf('[%s] Reprise de dossiers en erreur', $config['label'])),
        );

        return Command::SUCCESS;
    }
}
