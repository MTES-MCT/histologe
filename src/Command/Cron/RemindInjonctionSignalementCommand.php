<?php

namespace App\Command\Cron;

use App\Entity\Enum\SuiviCategory;
use App\Entity\Suivi;
use App\Manager\SuiviManager;
use App\Repository\SignalementRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\NotificationAndMailSender;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;

#[AsCommand(
    name: 'app:remind-injonction-signalement',
    description: 'Every month, remind bailleurs and usagers to give news about injonction signalements')]
class RemindInjonctionSignalementCommand extends AbstractCronCommand
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly SignalementRepository $signalementRepository,
        private readonly SuiviManager $suiviManager,
        #[Autowire(env: 'INJONCTION_REMINDER_THRESHOLD')]
        private readonly string $reminderThreshold,
        private readonly ClockInterface $clock,
        private readonly NotificationAndMailSender $notificationAndMailSender,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
    ) {
        parent::__construct($this->parameterBag);
    }

    /**
     * @throws \Exception
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $beforeDate = $this->clock->now()->modify('-'.$this->reminderThreshold);
        $signalements = $this->signalementRepository->findInjonctionToRemind($beforeDate);
        foreach ($signalements as $signalement) {
            if (!empty($signalement->getMailProprio())) {
                // Pour l'instant, on n'envoie pas de suivi : on envoie un mail simple
                $this->notificationAndMailSender->sendReminderToBailleur($signalement);
            }

            // Pour l'usager, on crée un suivi
            $description = 'Important - Point d\'avancement mensuel : ';
            $description .= 'Merci d\'indiquer si des démarches ont été entamées par votre bailleur (devis reçus, rdv artisans, travaux débutés, aucune avancée...).';
            $this->suiviManager->createSuivi(
                signalement: $signalement,
                description: $description,
                type: Suivi::TYPE_AUTO,
                category: SuiviCategory::INJONCTION_BAILLEUR_REMINDER_FOR_USAGER,
                isPublic: true
            );

            $output->writeln(sprintf('#%s reminded', $signalement->getUuid()));
        }

        $feedbackMsg = '';
        $countSignalement = count($signalements);
        if (count($signalements) > 0) {
            $feedbackMsg = \sprintf(
                '%s rappels ont été faits pour des signalements en injonction.',
                $countSignalement
            );
            $io->success($feedbackMsg);
        } else {
            $feedbackMsg = 'Aucun rappel n\'a été envoyé.';
            $io->warning($feedbackMsg);
        }

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: (string) $this->parameterBag->get('admin_email'),
                message: $feedbackMsg,
                cronLabel: 'rappel de mise à jour en cours d\'injonction',
                cronCount: null,
            )
        );

        return Command::SUCCESS;
    }
}
