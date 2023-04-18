<?php

namespace App\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Entity\Suivi;
use App\Factory\NotificationFactory;
use App\Factory\SuiviFactory;
use App\Manager\SuiviManager;
use App\Repository\UserRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

class InterventionCanceledSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SuiviFactory $suiviFactory,
        private SuiviManager $suiviManager,
        private NotificationMailerRegistry $notificationMailerRegistry,
        private Security $security,
        private NotificationFactory $notificationFactory,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.intervention_planning.transition.cancel' => 'onInterventionCanceled',
        ];
    }

    public function onInterventionCanceled(Event $event): void
    {
        $intervention = $event->getSubject();
        $user = $this->security->getUser();
        if (InterventionType::VISITE == $intervention->getType()) {
            $description = 'Annulation de visite :';
            $description .= ' la visite du logement prévue le '.$intervention->getDate()->format('d/m/Y');
            $description .= ' a été annulée pour le motif suivant : <br>';
            $description .= $intervention->getDetails();

            $suivi = $this->suiviFactory->createInstanceFrom(
                user: $user,
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
                            type: NotificationMailerType::TYPE_VISITE_CANCELED,
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
