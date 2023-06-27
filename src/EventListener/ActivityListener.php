<?php

namespace App\EventListener;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Notification;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\Territory;
use App\Entity\User;
use App\Factory\NotificationFactory;
use App\Repository\PartnerRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ActivityListener implements EventSubscriberInterface
{
    private $em;
    private $uow;
    private ArrayCollection $tos;

    public function __construct(
        private NotificationMailerRegistry $notificationMailerRegistry,
        private Security $security,
        private ParameterBagInterface $parameterBag,
        private PartnerRepository $partnerRepository,
        private NotificationFactory $notificationFactory,
    ) {
        $this->tos = new ArrayCollection();
        $this->uow = null;
        $this->em = null;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush,
            Events::preRemove,
        ];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $this->em = $args->getObjectManager();
        $this->uow = $this->em->getUnitOfWork();
        foreach ($this->uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof Signalement) {
                $this->notifyAdmins($entity, Notification::TYPE_NEW_SIGNALEMENT, $entity->getTerritory());
                $this->sendMail($entity, NotificationMailerType::TYPE_SIGNALEMENT_NEW);
            } elseif ($entity instanceof Affectation) {
                $partner = $entity->getPartner();
                $this->notifyPartner($partner, $entity, Notification::TYPE_AFFECTATION);
                $this->sendMail($entity, NotificationMailerType::TYPE_ASSIGNMENT_NEW);
            } elseif ($entity instanceof Suivi) {
                // pas de notification pour un suivi technique ou si intervention
                if (Suivi::TYPE_TECHNICAL === $entity->getType() || Suivi::CONTEXT_INTERVENTION === $entity->getContext()) {
                    continue;
                }

                $notifyAdminsAndPartners = $entity->getDescription() != $this->parameterBag->get('suivi_message')['first_accepted_affectation'];

                if ($notifyAdminsAndPartners) {
                    $this->notifyAdmins($entity, Notification::TYPE_SUIVI, $entity->getSignalement()->getTerritory());
                    $entity->getSignalement()->getAffectations()->filter(function (Affectation $affectation) use ($entity) {
                        $partner = $affectation->getPartner();
                        if (AffectationStatus::STATUS_WAIT->value === $affectation->getStatut()
                            || AffectationStatus::STATUS_ACCEPTED->value === $affectation->getStatut()) {
                            $this->notifyPartner($partner, $entity, Notification::TYPE_SUIVI);
                        }
                    });

                    if (Signalement::STATUS_CLOSED !== $entity->getSignalement()->getStatut()) {
                        $this->sendMail($entity, NotificationMailerType::TYPE_NEW_COMMENT_BACK);
                    }
                }

                if ($entity->getIsPublic() && Signalement::STATUS_REFUSED !== $entity->getSignalement()->getStatut()) {
                    $toRecipients = new ArrayCollection($entity->getSignalement()->getMailUsagers());
                    if (!$toRecipients->isEmpty() && Signalement::STATUS_CLOSED !== $entity->getSignalement()->getStatut()) {
                        $toRecipients->removeElement($entity->getCreatedBy()?->getEmail());
                        foreach ($toRecipients as $toRecipient) {
                            $this->notificationMailerRegistry->send(
                                new NotificationMail(
                                    type: NotificationMailerType::TYPE_NEW_COMMENT_FRONT,
                                    to: $toRecipient,
                                    territory: $entity->getSignalement()->getTerritory(),
                                    signalement: $entity->getSignalement(),
                                )
                            );
                        }
                    }
                }
            }
        }
    }

    private function getPartnerFromSignalementInsee(mixed $entity, Territory|null $territory): ?Partner
    {
        if ($entity instanceof Signalement) {
            $signalement = $entity;
        } else {
            /** @var Signalement $signalement */
            $signalement = $entity->getSignalement();
        }

        $authorizedInsee = $this->parameterBag->get('authorized_codes_insee');

        if (isset($authorizedInsee[$territory->getZip()])) {
            foreach ($authorizedInsee[$territory->getZip()] as $key => $authorizedInseePartner) {
                if (\in_array($signalement->getInseeOccupant(), $authorizedInseePartner)) {
                    return $this->partnerRepository->findOneBy(['nom' => $key]);
                }
            }
        }

        return null;
    }

    private function notifyAdmins($entity, $inAppType, Territory|null $territory)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->em->getRepository(User::class)->createQueryBuilder('u')
            ->andWhere('u.statut = 1')
            ->andWhere('JSON_CONTAINS(u.roles, :role) = 1 OR (JSON_CONTAINS(u.roles, :role2) = 1 AND u.territory = :territory)')
            ->setParameter('role', '"ROLE_ADMIN"')
            ->setParameter('role2', '"ROLE_ADMIN_TERRITORY"')
            ->setParameter('territory', $territory);

        if (null !== $partner = $this->getPartnerFromSignalementInsee($entity, $territory)) {
            $qb->andWhere('u.partner = :partner')->setParameter('partner', $partner);
        }

        foreach ($qb->getQuery()->getResult() as $admin) {
            $this->createInAppNotification($admin, $entity, $inAppType);
            if ($admin->getIsMailingActive()) {
                $this->tos[] = $admin->getEmail();
            }
        }
    }

    private function createInAppNotification($user, $entity, $type): void
    {
        if (!$entity instanceof Suivi && Notification::TYPE_SUIVI !== $type) {
            return;
        }

        if (Suivi::DESCRIPTION_SIGNALEMENT_VALIDE !== $entity->getDescription()) {
            $notification = $this->notificationFactory->createInstanceFrom($user, $entity);
            $this->em->persist($notification);
            $this->uow->computeChangeSet(
                $this->em->getClassMetadata(Notification::class),
                $notification
            );
        }
    }

    private function sendMail($entity, $mailType): void
    {
        $options = [];
        $options['entity'] = $entity;
        if ($entity instanceof Signalement) {
            $signalement = $entity;
        } else {
            /** @var Signalement $signalement */
            $signalement = $entity->getSignalement();
        }
        if (!$this->tos->isEmpty()) {
            $this->removeCurrentUserEmailForNotification();
            $this->tos = $this->tos->filter(function ($element) {
                return '' !== trim($element) && null !== $element;
            });

            if (!$this->tos->isEmpty()) {
                $this->notificationMailerRegistry->send(
                    new NotificationMail(
                        type: $mailType,
                        to: array_unique($this->tos->toArray()),
                        territory: $signalement->getTerritory(),
                        signalement: $signalement,
                        params: $options,
                    )
                );
                $this->tos->clear();
            }
        }
    }

    private function removeCurrentUserEmailForNotification(): void
    {
        $this->tos->removeElement($this->security?->getUser()?->getEmail());
    }

    private function notifyPartner($partner, $entity, $inAppType)
    {
        if ($partner->getEmail()) {
            $this->tos->add($partner->getEmail());
        }
        $partner->getUsers()->filter(function (User $user) use ($inAppType, $entity) {
            if (User::STATUS_ACTIVE === $user->getStatut() && !$user->isSuperAdmin() && !$user->isTerritoryAdmin()) {
                $this->createInAppNotification($user, $entity, $inAppType);
                if ($user->getIsMailingActive()) {
                    $this->tos->add($user->getEmail());
                }
            }
        });
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        if ($entity instanceof Affectation) {
            $entity->getNotifications()->filter(function (Notification $notification) use ($args) {
                $args->getObjectManager()->remove($notification);
            });
        }
    }
}
