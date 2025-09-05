<?php

namespace App\Command\Cron;

use App\Entity\Enum\PartnerType;
use App\Manager\JobEventManager;
use App\Repository\AffectationRepository;
use App\Repository\JobEventRepository;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\Interconnection\Esabora\EsaboraManager;
use App\Service\Interconnection\Esabora\Handler\InterventionSISHHandlerInterface;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'app:sync-esabora-sish-intervention',
    description: '[SISH] Commande qui permet de mettre à jour les interventions depuis Esabora',
)]
class SynchronizeInterventionSISHCommand extends AbstractSynchronizeEsaboraCommand
{
    /** @var iterable<InterventionSISHHandlerInterface> */
    private iterable $interventionHandlers;

    /** @param iterable<InterventionSISHHandlerInterface> $interventionHandlers */
    public function __construct(
        private readonly EsaboraManager $esaboraManager,
        private readonly JobEventManager $jobEventManager,
        private readonly AffectationRepository $affectationRepository,
        private readonly SerializerInterface $serializer,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly ParameterBagInterface $parameterBag,
        private readonly EntityManagerInterface $entityManager,
        #[AutowireIterator(
            'app.intervention_sish_handler',
            defaultPriorityMethod: 'getPriority'
        )] iterable $interventionHandlers,
    ) {
        parent::__construct(
            $this->esaboraManager,
            $this->affectationRepository,
            $this->serializer,
            $this->notificationMailerRegistry,
            $this->parameterBag,
            $this->entityManager,
        );
        $this->interventionHandlers = $interventionHandlers;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $countSuccess = $countFailed = 0;
        $errorMessages = [];

        $io = new SymfonyStyle($input, $output);
        $uuidSignalement = $input->getArgument('uuid_signalement') ?? null;
        $affectations = $this->affectationRepository->findAffectationSubscribedToEsabora(
            partnerType: PartnerType::ARS,
            uuidSignalement: $uuidSignalement
        );
        $count = 0;
        foreach ($affectations as $row) {
            $affectation = $row['affectation'];
            /** @var InterventionSISHHandlerInterface $interventionHandler */
            foreach ($this->interventionHandlers as $key => $interventionHandler) {
                try {
                    $interventionHandler->handle($affectation, $row['signalement_uuid']);
                    $io->writeln(\sprintf('#%s: %s was executed', $key, $interventionHandler->getServiceName()));
                } catch (\Throwable $e) {
                    $signalement = $affectation->getSignalement();
                    $message = $interventionHandler->getServiceName()
                        .' - Signalement '.$row['signalement_uuid']
                        .' ('.$signalement->getId().') : '
                        .$e->getMessage();
                    if (!($e instanceof \Exception)) {
                        $message .= ' - '.$e->getFile().' ('.$e->getLine().')';
                    }
                    $io->error($message);
                    $errorMessages[] = $message;
                }
            }
            ++$count;
            if (0 === $count % self::BATCH_SIZE) {
                $this->entityManager->flush();
            }
        }
        $this->entityManager->flush();
        /** @var JobEventRepository $jobEventRepository */
        $jobEventRepository = $this->jobEventManager->getRepository();
        ['success_count' => $countSuccess, 'failed_count' => $countFailed] =
             $jobEventRepository->getReportEsaboraAction(
                 AbstractEsaboraService::ACTION_SYNC_DOSSIER_ARRETE,
                 AbstractEsaboraService::ACTION_SYNC_DOSSIER_VISITE
             );

        $io->table(['Count success', 'Count Failed'], [[$countSuccess, $countFailed]]);
        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: (string) $this->parameterBag->get('admin_email'),
                cronLabel: '[ARS] Synchronisation des interventions depuis Esabora',
                params: [
                    'count_success' => $countSuccess,
                    'count_failed' => $countFailed,
                    'message_success' => $countSuccess > 1
                        ? 'synchronisations ont été effectuées'
                        : 'synchronisation effectuée',
                    'message_failed' => $countFailed > 1
                        ? 'synchronisations en échec'
                        : 'synchronisation en échec',
                    'error_messages' => $errorMessages,
                ],
            )
        );

        return Command::SUCCESS;
    }
}
