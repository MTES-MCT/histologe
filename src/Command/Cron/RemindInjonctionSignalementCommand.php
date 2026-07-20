<?php

namespace App\Command\Cron;

use App\Dto\SignalementAffectationClose;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Suivi;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Repository\SignalementRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Notification\NotificationAndMailSender;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly SignalementManager $signalementManager,
        #[Autowire(env: 'INJONCTION_REMINDER_THRESHOLD')]
        private readonly string $reminderSuiviTravauxThreshold,
        #[Autowire(env: 'INJONCTION_ANSWER_BAILLEUR_THRESHOLD')]
        private readonly string $answerBailleurThreshold,
        #[Autowire(env: 'INJONCTION_USAGER_CLOTURE_THRESHOLD')]
        private readonly string $usagerClotureThreshold,
        #[Autowire(env: 'INJONCTION_USAGER_CLOTURE_AND_CLOSE_THRESHOLD')]
        private readonly string $usagerClotureAndCloseThreshold,
        private readonly ClockInterface $clock,
        private readonly NotificationAndMailSender $notificationAndMailSender,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly EntityManagerInterface $entityManager,
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

        $this->remindAnswerBailleur($io, $output);
        $this->remindSuiviTravaux($io, $output);
        $this->remindUsagerForCloture($io, $output);
        $this->remindUsagerForClotureAndClose($io, $output);
        $this->entityManager->flush();

        return Command::SUCCESS;
    }

    private function remindAnswerBailleur(SymfonyStyle $io, OutputInterface $output): void
    {
        $beforeDate = $this->clock->now()->modify('-'.$this->answerBailleurThreshold);
        $signalements = $this->signalementRepository->findInjonctionToRemindAnswerBailleur($beforeDate);
        foreach ($signalements as $signalement) {
            if (!empty($signalement->getMailProprio())) {
                $this->notificationAndMailSender->sendNewSignalementInjonction($signalement);
                $output->writeln(sprintf('#%s bailleur reminded to answer', $signalement->getUuid()));

                // On crée un suivi pour l'usager
                $description = 'Le bailleur du logement a été relancé pour répondre à l\'injonction.';
                $this->suiviManager->createSuivi(
                    signalement: $signalement,
                    description: $description,
                    category: SuiviCategory::INJONCTION_BAILLEUR_RAPPEL_REPONSE_BAILLEUR,
                );
            }
        }

        $feedbackMsg = '';
        $countSignalement = count($signalements);
        if (count($signalements) > 0) {
            $feedbackMsg = \sprintf(
                '%s rappels faits pour des signalements sans réponse bailleur.',
                $countSignalement
            );
            $io->success($feedbackMsg);
        } else {
            $feedbackMsg = 'Aucun rappel n\'a été envoyé pour les bailleurs.';
            $io->warning($feedbackMsg);
        }

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: (string) $this->parameterBag->get('admin_email'),
                message: $feedbackMsg,
                cronLabel: 'rappel de réponse du bailleur en cours d\'injonction',
                cronCount: null,
            )
        );
    }

    private function remindSuiviTravaux(SymfonyStyle $io, OutputInterface $output): void
    {
        $beforeDate = $this->clock->now()->modify('-'.$this->reminderSuiviTravauxThreshold);
        $signalementsLastMessageBailleur = $this->signalementRepository->findInjonctionToRemind($beforeDate, 'bailleur');
        foreach ($signalementsLastMessageBailleur as $signalement) {
            if (!empty($signalement->getMailProprio())) {
                $this->notificationAndMailSender->sendReminderToBailleur($signalement);
            }

            $description = 'Relance envoyée au bailleur pour demander un suivi sur les travaux.';
            $this->suiviManager->createSuivi(
                signalement: $signalement,
                description: $description,
                category: SuiviCategory::INJONCTION_BAILLEUR_REMINDER_FOR_BAILLEUR,
            );

            $output->writeln(sprintf('#%s reminded', $signalement->getUuid()));
        }
        $signalementsLastMessageUsager = $this->signalementRepository->findInjonctionToRemind($beforeDate, 'usager');
        foreach ($signalementsLastMessageUsager as $signalement) {
            // Pour l'usager, on crée un suivi
            $description = 'Important - Point d\'avancement mensuel : ';
            $description .= 'Merci d\'indiquer si des démarches ont été entamées par votre bailleur (devis reçus, rdv artisans, travaux débutés, aucune avancée...).';
            $this->suiviManager->createSuivi(
                signalement: $signalement,
                description: $description,
                category: SuiviCategory::INJONCTION_BAILLEUR_REMINDER_FOR_USAGER,
                isVisibleForUsager: true
            );

            $output->writeln(sprintf('#%s reminded', $signalement->getUuid()));
        }

        $feedbackMsg = '';
        $countSignalementLastMessageBailleur = count($signalementsLastMessageBailleur);
        if ($countSignalementLastMessageBailleur > 0) {
            $feedbackMsgBailleur = \sprintf(
                '%s rappels faits pour les bailleurs pour des signalements avec suivi travaux.',
                $countSignalementLastMessageBailleur
            );
            $io->success($feedbackMsgBailleur);
            $feedbackMsg = $feedbackMsgBailleur;
        }
        $countSignalementLastMessageUsager = count($signalementsLastMessageUsager);
        if ($countSignalementLastMessageUsager > 0) {
            $feedbackMsgUsager = \sprintf(
                '%s rappels faits pour les usagers pour des signalements avec suivi travaux.',
                $countSignalementLastMessageUsager
            );
            $io->success($feedbackMsgUsager);
            $feedbackMsg .= ' '.$feedbackMsgUsager;
        }

        if (0 === $countSignalementLastMessageBailleur && 0 === $countSignalementLastMessageUsager) {
            $feedbackMsg = 'Aucun rappel n\'a été envoyé pour le suivi.';
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
    }

    private function remindUsagerForCloture(SymfonyStyle $io, OutputInterface $output): void
    {
        // Si le bailleur a demandé la clôture et que 15 jours plus tard il n'y a pas de réponse usager,
        // on relance le déclarant (mail template 318) + suivi automatique dédié.
        $beforeDate = $this->clock->now()->modify('-'.$this->usagerClotureThreshold);
        $signalements = $this->signalementRepository->findInjonctionClotureBailleurToRemindUsager($beforeDate);
        foreach ($signalements as $signalement) {
            if (!empty($signalement->getMailOccupant())) {
                $this->notificationAndMailSender->sendReminderClotureToUsager($signalement);
            }

            $description = 'Relance envoyée à l\'usager pour lui demander de confirmer la réalisation des travaux déclarée par le bailleur il y a 15 jours.';
            $this->suiviManager->createSuivi(
                signalement: $signalement,
                description: $description,
                category: SuiviCategory::INJONCTION_BAILLEUR_RELANCE_USAGER_CLOTURE,
                sendMail: false,
            );

            $output->writeln(sprintf('#%s reminded', $signalement->getUuid()));
        }

        $countSignalements = count($signalements);
        if ($countSignalements > 0) {
            $feedbackMsgBailleur = \sprintf(
                '%s rappels envoyés à l\'usager suite à une demande de clôture par le bailleur au bout de 15 jours.',
                $countSignalements
            );
            $io->success($feedbackMsgBailleur);
            $feedbackMsg = $feedbackMsgBailleur;
        } else {
            $feedbackMsg = 'Aucun rappel n\'a été envoyé pour l\'usager suite à une demande de clôture par le bailleur au bout de 15 jours.';
            $io->warning($feedbackMsg);
        }

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: (string) $this->parameterBag->get('admin_email'),
                message: $feedbackMsg,
                cronLabel: 'rappel usager suite demande de cloture par bailleur au bout de 15 jours',
                cronCount: null,
            )
        );
    }

    private function remindUsagerForClotureAndClose(SymfonyStyle $io, OutputInterface $output): void
    {
        // Si le bailleur a demandé la clôture et que 30 jours plus tard il n'y a toujours pas de réponse usager,
        // on clôture le dossier (motif Résolution), et on notifie l'usager (template 320) et le bailleur (template 319).
        $beforeDate = $this->clock->now()->modify('-'.$this->usagerClotureAndCloseThreshold);
        $signalements = $this->signalementRepository->findInjonctionClotureBailleurToClose($beforeDate);
        $description = 'En l\'absence de réponse ou d\'opposition du déclarant dans le délai imparti, le dossier est clôturé et réputé résolu.';
        foreach ($signalements as $signalement) {
            if (!empty($signalement->getMailOccupant())) {
                $this->notificationAndMailSender->sendReminderClotureAndCloseToUsager($signalement);
            }
            if (!empty($signalement->getMailProprio())) {
                $this->notificationAndMailSender->sendReminderClotureAndCloseToBailleur($signalement);
            }

            $signalementAffectationClose = (new SignalementAffectationClose())
                ->setSignalement($signalement)
                ->setMotifCloture(MotifCloture::TRAVAUX_FAITS_OU_EN_COURS)
                ->setDescription($description);
            $this->signalementManager->closeSignalement($signalementAffectationClose);

            $this->suiviManager->createSuivi(
                signalement: $signalement,
                description: $description,
                category: SuiviCategory::SIGNALEMENT_IS_CLOSED,
                isVisibleForUsager: true,
                isVisibleForBailleur: true,
                sendMail: false,
            );

            $output->writeln(sprintf('#%s closed', $signalement->getUuid()));
        }

        $countSignalements = count($signalements);
        if ($countSignalements > 0) {
            $feedbackMsgBailleur = \sprintf(
                '%s rappels envoyés à l\'usager et au bailleur suite à une demande de clôture par le bailleur au bout de 30 jours.',
                $countSignalements
            );
            $io->success($feedbackMsgBailleur);
            $feedbackMsg = $feedbackMsgBailleur;
        } else {
            $feedbackMsg = 'Aucun rappel n\'a été envoyé pour l\'usager ou le bailleursuite à une demande de clôture par le bailleur au bout de 30 jours.';
            $io->warning($feedbackMsg);
        }

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: (string) $this->parameterBag->get('admin_email'),
                message: $feedbackMsg,
                cronLabel: 'rappel usager et bailleur suite demande de cloture par bailleur au bout de 30 jours',
                cronCount: null,
            )
        );
    }
}
