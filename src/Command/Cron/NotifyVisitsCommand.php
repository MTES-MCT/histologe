<?php

namespace App\Command\Cron;

use App\Entity\Intervention;
use App\Entity\Suivi;
use App\Manager\InterventionManager;
use App\Manager\SuiviManager;
use App\Repository\AffectationRepository;
use App\Repository\InterventionRepository;
use App\Repository\SuiviRepository;
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
        private InterventionRepository $interventionRepository,
        private InterventionManager $interventionManager,
        private AffectationRepository $affectationRepository,
        private SuiviManager $suiviManager,
        private SuiviRepository $suiviRepository,
        private VisiteNotifier $visiteNotifier,
        private NotificationMailerRegistry $notificationMailerRegistry,
        private ParameterBagInterface $parameterBag,
    ) {
        parent::__construct($this->parameterBag);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $countFutureVisits = 0;
        $countPastVisits = 0;
        $countVisitsToPlan = 0;

        $listFutureVisits = $this->interventionRepository->getFutureVisits();
        foreach ($listFutureVisits as $intervention) {
            $partnerName = $intervention->getPartner() ? $intervention->getPartner()->getNom() : 'Non renseigné';
            $signalement = $intervention->getSignalement();
            $description = '<strong>Rappel de visite :</strong> la visite du logement situé ';
            $description .= $signalement->getAdresseOccupant().' '.$signalement->getCpOccupant().' '.$signalement->getVilleOccupant();
            $description .= ' aura lieu le '.$intervention->getScheduledAt()->format('d/m/Y');
            $description .= '<br>La visite sera effectuée par '.$partnerName.'.';
            $suivi = $this->suiviManager->createSuivi(
                user: null,
                signalement: $intervention->getSignalement(),
                params: [
                    'description' => $description,
                    'type' => Suivi::TYPE_TECHNICAL,
                ],
                isPublic: true,
                context: Suivi::CONTEXT_INTERVENTION,
            );
            $this->suiviManager->save($suivi);

            $this->visiteNotifier->notifyUsagers($intervention, NotificationMailerType::TYPE_VISITE_FUTURE_REMINDER_TO_USAGER);

            $this->visiteNotifier->notifyAgents(
                intervention: $intervention,
                suivi: $suivi,
                currentUser: null,
                notificationMailerType: NotificationMailerType::TYPE_VISITE_FUTURE_REMINDER_TO_PARTNER,
                notifyAdminTerritory: false,
            );

            $intervention->setReminderBeforeSentAt(new \DateTimeImmutable());
            $this->interventionManager->save($intervention);

            ++$countFutureVisits;
        }

        $listPastVisits = $this->interventionRepository->getPastVisits();
        foreach ($listPastVisits as $intervention) {
            $pastVisiteReminderUsers = $this->getPastVisiteReminderUsers($intervention);
            foreach ($pastVisiteReminderUsers as $user) {
                $this->notificationMailerRegistry->send(
                    new NotificationMail(
                        type: NotificationMailerType::TYPE_VISITE_PAST_REMINDER_TO_PARTNER,
                        to: $user->getEmail(),
                        territory: $intervention->getSignalement()->getTerritory(),
                        signalement: $intervention->getSignalement(),
                        intervention: $intervention,
                    )
                );
            }
            $intervention->setReminderConclusionSentAt(new \DateTimeImmutable());
            $this->interventionManager->save($intervention);
            ++$countPastVisits;
        }

        // Notifs for visits that should be planned
        if (!empty($this->parameterBag->get('feature_ask_visite'))) {
            $listAffectations = $this->affectationRepository->findAcceptedAffectationsFromVisitesPartner();
            foreach ($listAffectations as $affectation) {
                if (0 == \count($affectation->getSignalement()->getInterventions())) {
                    $description = 'Aucune information de visite n\'a été renseignée pour le logement.';
                    $description .= ' Merci de programmer une visite dès que possible !';
                    $suivi = $this->suiviManager->createSuivi(
                        user: null,
                        signalement: $affectation->getSignalement(),
                        isPublic: false,
                        context: Suivi::CONTEXT_INTERVENTION,
                        params: [
                            'description' => $description,
                            'type' => Suivi::TYPE_TECHNICAL,
                        ],
                    );
                    $this->suiviManager->save($suivi);

                    $this->visiteNotifier->notifyAgents(
                        intervention: null,
                        suivi: $suivi,
                        currentUser: null,
                        notificationMailerType: NotificationMailerType::TYPE_VISITE_NEEDED,
                        notifyAdminTerritory: false,
                        affectation: $affectation,
                    );

                    ++$countVisitsToPlan;
                }
            }
        }

        $description = 'notifications ont été envoyées pour des visites à venir';
        $description .= ' --- '.$countPastVisits.' notifications ont été envoyées pour des visites passées';
        $description .= ' --- '.$countVisitsToPlan.' notifications ont été envoyées pour des visites non planifiées';
        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: $this->parameterBag->get('admin_email'),
                cronLabel: 'Envoi de notifications de visites',
                params: [
                    'count_success' => $countFutureVisits,
                    'message_success' => $description,
                ],
            )
        );

        return Command::SUCCESS;
    }

    private function getPastVisiteReminderUsers(Intervention $intervention): array
    {
        $usersToNotify = [];

        $partnerUsers = $intervention->getPartner()->getUsers();
        foreach ($partnerUsers as $user) {
            if (\in_array('ROLE_ADMIN_PARTNER', $user->getRoles())) {
                $usersToNotify[] = $user;
            }
        }

        $agentToNotify = null;
        $suivisLinkedToSignalement = $this->suiviRepository->findSuivisByContext($intervention->getSignalement(), Suivi::CONTEXT_INTERVENTION);
        foreach ($suivisLinkedToSignalement as $suivi) {
            if ($suivi->getCreatedBy()?->getPartner() == $intervention->getPartner()) {
                $agentToNotify = $suivi->getCreatedBy();
                $usersToNotify[] = $agentToNotify;
                break;
            }
        }

        if (!$agentToNotify) {
            foreach ($suivisLinkedToSignalement as $suivi) {
                if ($suivi->getCreatedBy() && \in_array('ROLE_ADMIN_TERRITORY', $suivi->getCreatedBy()->getRoles())) {
                    $usersToNotify[] = $suivi->getCreatedBy();
                    break;
                }
            }
        }

        return $usersToNotify;
    }
}
