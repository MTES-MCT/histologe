<?php

namespace App\Command\Cron;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Suivi;
use App\Manager\InterventionManager;
use App\Manager\SuiviManager;
use App\Repository\AffectationRepository;
use App\Repository\InterventionRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\VisiteNotifier;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:notify-visits',
    description: 'Sends notifications concerning visits'
)]
class NotifyVisitsCommand extends AbstractCronCommand
{
    public function __construct(
        private readonly InterventionRepository $interventionRepository,
        private readonly InterventionManager $interventionManager,
        private readonly AffectationRepository $affectationRepository,
        private readonly SuiviManager $suiviManager,
        private readonly VisiteNotifier $visiteNotifier,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly ParameterBagInterface $parameterBag,
    ) {
        parent::__construct($this->parameterBag);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // histologe is the name of the production scalingo app
        // test is injected in NotifyVisitsCommandTest
        // dev is for local development
        if ('histologe' !== getenv('APP') && 'test' !== getenv('APP') && 'dev' !== $_ENV['APP_ENV']) {
            $io->error('This command is only available on production environment, test environment and dev environment');

            return Command::FAILURE;
        }

        $countFutureVisits = 0;
        $countPastVisits = 0;
        $countVisitsToPlan = 0;

        $listFutureVisits = $this->interventionRepository->getFutureVisits();
        foreach ($listFutureVisits as $intervention) {
            $partnerName = $intervention->getPartner() ? $intervention->getPartner()->getNom() : 'Non renseigné';
            $signalement = $intervention->getSignalement();
            if (!$signalement || SignalementStatus::ACTIVE !== $signalement->getStatut()) {
                continue;
            }
            $isLogementVacant = $signalement->getIsLogementVacant();
            $description = '<strong>Rappel de visite :</strong> la visite du logement situé ';
            $description .= $signalement->getAdresseOccupant().' '.$signalement->getCpOccupant().' '.$signalement->getVilleOccupant();
            $description .= ' aura lieu le '.$intervention->getScheduledAt()->format('d/m/Y');
            $description .= '<br>La visite sera effectuée par '.$partnerName.'.';
            $suivi = $this->suiviManager->createSuivi(
                signalement: $signalement,
                description: $description,
                type: Suivi::TYPE_TECHNICAL,
                category: SuiviCategory::INTERVENTION_PLANNED_REMINDER,
                isPublic: !$isLogementVacant,
                context: Suivi::CONTEXT_INTERVENTION,
            );

            if (!$isLogementVacant) {
                $this->visiteNotifier->notifyUsagers(
                    intervention: $intervention,
                    notificationMailerType: NotificationMailerType::TYPE_VISITE_FUTURE_REMINDER_TO_USAGER,
                    suivi: $suivi
                );
            }

            $this->visiteNotifier->notifySubscribers(
                notificationMailerType: NotificationMailerType::TYPE_VISITE_FUTURE_REMINDER_TO_PARTNER,
                intervention: $intervention,
                suivi: $suivi,
            );

            $intervention->setReminderBeforeSentAt(new \DateTimeImmutable());
            $this->interventionManager->save($intervention);

            ++$countFutureVisits;
        }

        $listPastVisits = $this->interventionRepository->getPastVisits();
        foreach ($listPastVisits as $intervention) {
            $signalement = $intervention->getSignalement();

            if (!$signalement || SignalementStatus::ACTIVE !== $signalement->getStatut()) {
                continue;
            }
            $this->visiteNotifier->notifyInterventionSubscribers(
                notificationMailerType: NotificationMailerType::TYPE_VISITE_PAST_REMINDER_TO_PARTNER,
                intervention: $intervention,
            );

            $intervention->setReminderConclusionSentAt(new \DateTimeImmutable());
            $this->interventionManager->save($intervention);
            ++$countPastVisits;
        }

        // Notifs for visits that should be planned
        $listAffectations = $this->affectationRepository->findAcceptedAffectationsFromVisitesPartner();
        foreach ($listAffectations as $affectation) {
            if (0 == \count($affectation->getSignalement()->getInterventions())) {
                $description = 'La réalisation d\'une visite est nécessaire pour caractériser les désordres signalés.';
                $description .= ' Merci de renseigner la date ou les conclusions de la visite afin de poursuivre la prise en charge de ce signalement.';
                $suivi = $this->suiviManager->createSuivi(
                    signalement: $affectation->getSignalement(),
                    description: $description,
                    type: Suivi::TYPE_TECHNICAL,
                    category: SuiviCategory::INTERVENTION_IS_REQUIRED,
                    isPublic: false,
                    context: Suivi::CONTEXT_INTERVENTION,
                );

                $this->visiteNotifier->notifyAffectationSubscribers(
                    notificationMailerType: NotificationMailerType::TYPE_VISITE_NEEDED,
                    affectation: $affectation,
                    suivi: $suivi
                );
                ++$countVisitsToPlan;
            }
        }

        $description = 'notifications ont été envoyées pour des visites à venir';
        $description .= ' --- '.$countPastVisits.' notifications ont été envoyées pour des visites passées';
        $description .= ' --- '.$countVisitsToPlan.' notifications ont été envoyées pour des visites non planifiées';
        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: (string) $this->parameterBag->get('admin_email'),
                cronLabel: 'Envoi de notifications de visites',
                params: [
                    'count_success' => $countFutureVisits,
                    'message_success' => $description,
                ],
            )
        );

        return Command::SUCCESS;
    }
}
