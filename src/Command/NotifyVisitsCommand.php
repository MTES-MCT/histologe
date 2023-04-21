<?php

namespace App\Command;

use App\Entity\Suivi;
<<<<<<< HEAD
use App\Manager\InterventionManager;
use App\Manager\SuiviManager;
=======
>>>>>>> 9a7ae509 (notifications for visites j+2 and affectations j+15)
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
class NotifyVisitsCommand extends Command
{
    public function __construct(
        private InterventionRepository $interventionRepository,
<<<<<<< HEAD
        private InterventionManager $interventionManager,
        private AffectationRepository $affectationRepository,
        private SuiviManager $suiviManager,
=======
        private AffectationRepository $affectationRepository,
>>>>>>> 9a7ae509 (notifications for visites j+2 and affectations j+15)
        private VisiteNotifier $visiteNotifier,
        private NotificationMailerRegistry $notificationMailerRegistry,
        private ParameterBagInterface $parameterBag,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $countFutureVisits = 0;
        $countPastVisits = 0;
        $countVisitsToPlan = 0;

        // Future visits
        $listFutureVisits = $this->interventionRepository->getFutureVisits();
        foreach ($listFutureVisits as $intervention) {
            $signalement = $intervention->getSignalement();
            $description = '<strong>Rappel de visite :</strong> la visite du logement situé';
            $description .= $signalement->getAdresseOccupant().' '.$signalement->getCpOccupant().' '.$signalement->getVilleOccupant();
            $description .= ' aura lieu le '.$intervention->getScheduledAt()->format('d/m/Y');
            $description .= '<br>La visite sera effectuée par '.$intervention->getPartner()->getNom().'.';
            $suivi = $this->suiviManager->createSuivi(
                user: null,
                signalement: $intervention->getSignalement(),
                isPublic: true,
                context: Suivi::CONTEXT_INTERVENTION,
                params: [
                    'description' => $description,
                    'type' => Suivi::TYPE_TECHNICAL,
                ],
            );
            $this->suiviManager->save($suivi);

            $this->visiteNotifier->notifyUsagers($intervention, NotificationMailerType::TYPE_VISITE_FUTURE_REMINDER_TO_USAGER);

            $this->visiteNotifier->notifyAgents(
                intervention: $intervention,
                suivi: $suivi,
                currentUser: null,
                notificationMailerType: NotificationMailerType::TYPE_VISITE_FUTURE_REMINDER_TO_PARTNER,
            );

<<<<<<< HEAD
            $intervention->setReminderBeforeSentAt(new \DateTimeImmutable());
            $this->interventionManager->save($intervention);

            ++$countFutureVisits;
        }

=======
            ++$countFutureVisits;
        }

        // Past visits
>>>>>>> 9a7ae509 (notifications for visites j+2 and affectations j+15)
        $listPastVisits = $this->interventionRepository->getPastVisits();
        foreach ($listPastVisits as $intervention) {
            foreach ($intervention->getPartner()->getUsers() as $user) {
                $this->notificationMailerRegistry->send(
                    new NotificationMail(
                        type: NotificationMailerType::TYPE_VISITE_PAST_REMINDER_TO_PARTNER,
                        to: $user->getEmail(),
                        territory: $intervention->getSignalement()->getTerritory(),
                        signalement: $intervention->getSignalement(),
<<<<<<< HEAD
                        intervention: $intervention,
                    )
                );
            }
            $intervention->setReminderConclusionSentAt(new \DateTimeImmutable());
            $this->interventionManager->save($intervention);
=======
                    )
                );
            }
>>>>>>> 9a7ae509 (notifications for visites j+2 and affectations j+15)
            ++$countPastVisits;
        }
        
        // Notifs for visits that should be planned
        if (!empty($this->parameterBag->get('feature_ask_visite'))) {
            $listAffectations = $this->affectationRepository->findAffectationsCheckVisite();
            foreach ($listAffectations as $affectation) {
                if (count($affectation->getSignalement()->getInterventions()) == 0) {
                    $description = 'Aucune information de visite n\'a été renseignée pour le logement.';
                    $description .= ' Merci de programmer une visite dès que possible !';
                    $suivi = $this->visiteNotifier->createSuivi(
                        description: $description,
                        currentUser: null,
                        signalement: $intervention->getSignalement(),
                        typeSuivi: Suivi::TYPE_TECHNICAL,
                        isPublic: false,
                    );
        
                    // Send notifications to agents
                    $this->visiteNotifier->notifyAgents(
                        intervention: $intervention,
                        suivi: $suivi,
                        currentUser: null,
                        notificationMailerType: NotificationMailerType::TYPE_VISITE_NEEDED,
                        notifyAdminTerritory: false,
                    );

<<<<<<< HEAD
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
=======
                    $countVisitsToPlan++;
                }
            }
        }
>>>>>>> 9a7ae509 (notifications for visites j+2 and affectations j+15)

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
}
