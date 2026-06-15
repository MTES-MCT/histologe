<?php

namespace App\Command\Cron;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\PartnerType;
use App\Repository\AffectationRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:desync-esabora-sish',
    description: '[SISH] Désynchronise les affectations refusées depuis plus de 2 jours',
)]
class DesynchronizeEsaboraSISHCommand extends AbstractCronCommand
{
    public const int REFUSED_DESYNC_DELAY_IN_DAYS = 2;
    public const int BATCH_SIZE = 100;

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly AffectationRepository $affectationRepository,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct($this->parameterBag);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $affectations = $this->affectationRepository->findAffectationSubscribedToEsabora(
            partnerType: [PartnerType::ARS, PartnerType::COMMUNE_SCHS],
            affectationStatus: AffectationStatus::REFUSED,
            nbDaysBeforeDesync: self::REFUSED_DESYNC_DELAY_IN_DAYS
        );

        $progressBar = $io->createProgressBar(\count($affectations));
        $progressBar->start();

        $countSuccess = 0;

        foreach ($affectations as $row) {
            $progressBar->advance();

            /** @var Affectation $affectation */
            $affectation = $row['affectation'];
            $partner = $affectation->getPartner();
            if (!$partner->isConnectedToSanteHabitat()) {
                continue;
            }

            $affectation->setIsSynchronized(false);
            ++$countSuccess;

            if (0 === ($countSuccess % self::BATCH_SIZE)) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();

        $progressBar->finish();
        $io->newLine();

        $this->notify($countSuccess);

        $io->success(sprintf(
            '%d affectation(s) ont été désynchronisée(s) de Santé Habitat.',
            $countSuccess
        ));

        return Command::SUCCESS;
    }

    private function notify(int $countSuccess): void
    {
        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: (string) $this->parameterBag->get('admin_email'),
                cronLabel: '[ARS] Desynchronisation des signalements refusés par SI-SH',
                params: [
                    'count_success' => $countSuccess,
                    'message_success' => $countSuccess > 1
                        ? 'désynchronisations ont été effectuées'
                        : 'désynchronisation effectuée',
                ],
            )
        );
    }
}
