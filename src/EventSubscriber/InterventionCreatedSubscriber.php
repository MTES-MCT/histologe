<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Entity\Suivi;
use App\Event\InterventionCreatedEvent;
use App\Factory\NotificationFactory;
use App\Factory\SuiviFactory;
use App\Manager\SuiviManager;
use App\Repository\UserRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InterventionCreatedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SuiviFactory $suiviFactory,
        private SuiviManager $suiviManager,
        private NotificationMailerRegistry $notificationMailerRegistry,
        private NotificationFactory $notificationFactory,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InterventionCreatedEvent::NAME => 'onInterventionCreated',
        ];
    }

    public function onInterventionCreated(InterventionCreatedEvent $event): void
    {
        $intervention = $event->getIntervention();
        if (InterventionType::VISITE == $intervention->getType()) {
            $description = 'Visite programmée : une visite du logement situé '.$intervention->getSignalement()->getAdresseOccupant();
            $description .= ' est prévue le '.$intervention->getDate()->format('d/m/Y').'.';
            $description .= '<br>';
            $description .= 'La visite sera effectuée par '.$intervention->getPartner()->getNom().'.';

            $suivi = $this->suiviFactory->createInstanceFrom(
                user: $event->getUser(),
                signalement: $intervention->getSignalement(),
                params: [
                    'type' => SUIVI::TYPE_AUTO,
                    'description' => $description,
                ],
                isPublic: true,
                context: Suivi::CONTEXT_INTERVENTION,
            );
            $this->suiviManager->save($suivi);

            $toRecipients = new ArrayCollection($intervention->getSignalement()->getMailUsagers());
            if (!$toRecipients->isEmpty()) {
                foreach ($toRecipients as $toRecipient) {
                    $this->notificationMailerRegistry->send(
                        new NotificationMail(
                            type: NotificationMailerType::TYPE_VISITE_CREATED,
                            to: $toRecipient,
                            territory: $intervention->getSignalement()->getTerritory(),
                            signalement: $intervention->getSignalement(),
                            intervention: $intervention,
                        )
                    );
                }
            }

            $listUsersAdmin = $this->userRepository->findActiveTerritoryAdmins($intervention->getSignalement()->getTerritory());
            foreach ($listUsersAdmin as $user) {
                // TODO : sauf si auteur
                $notification = $this->notificationFactory->createInstanceFrom($user, $suivi);
                $this->entityManager->persist($notification);
                $this->entityManager->flush();
            }
            // TODO : notif aux agents
        }
    }
}
