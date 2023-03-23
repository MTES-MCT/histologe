<?php

namespace App\EventListener;

use App\Entity\Affectation;
use App\Entity\Notification;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\Territory;
use App\Entity\User;
use App\Repository\PartnerRepository;
use App\Service\NotificationService;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ActivityListener implements EventSubscriberInterface
{
    private $em;
    private $uow;
    private ArrayCollection $tos;

    public function __construct(
        private NotificationService $notifier,
        private UrlGeneratorInterface $urlGenerator,
        private Security $security,
        private ParameterBagInterface $parameterBag,
        private PartnerRepository $partnerRepository,
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
        $this->em = $args->getEntityManager();
        $this->uow = $this->em->getUnitOfWork();
        foreach ($this->uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof Signalement) {
                $this->notifyAdmins($entity, Notification::TYPE_NEW_SIGNALEMENT, $entity->getTerritory());
                $this->sendMail($entity, NotificationService::TYPE_SIGNALEMENT_NEW);
            } elseif ($entity instanceof Affectation) {
                $partner = $entity->getPartner();
                $this->notifyPartner($partner, $entity, Notification::TYPE_AFFECTATION);
                $this->sendMail($entity, NotificationService::TYPE_ASSIGNMENT_NEW);
            } elseif ($entity instanceof Suivi) {
                // pas de notification pour un suivi technique
                if (Suivi::TYPE_TECHNICAL === $entity->getType()) {
                    continue;
                }

                $this->notifyAdmins($entity, Notification::TYPE_SUIVI, $entity->getSignalement()->getTerritory());
                $entity->getSignalement()->getAffectations()->filter(function (Affectation $affectation) use ($entity) {
                    $partner = $affectation->getPartner();
                    $this->notifyPartner($partner, $entity, Notification::TYPE_SUIVI);
                });

                if (Signalement::STATUS_CLOSED !== $entity->getSignalement()->getStatut()) {
                    $this->sendMail($entity, NotificationService::TYPE_NEW_COMMENT_BACK);
                }

                if ($entity->getIsPublic() && Signalement::STATUS_REFUSED !== $entity->getSignalement()->getStatut()) {
                    $toRecipients = new ArrayCollection($entity->getSignalement()->getMailUsagers());
                    if (!$toRecipients->isEmpty() && Signalement::STATUS_CLOSED !== $entity->getSignalement()->getStatut()) {
                        $toRecipients->removeElement($entity->getCreatedBy()?->getEmail());
                        foreach ($toRecipients as $toRecipient) {
                            $this->notifier->send(
                                NotificationService::TYPE_NEW_COMMENT_FRONT,
                                [$toRecipient],
                                [
                                    'signalement' => $entity->getSignalement(),
                                    'lien_suivi' => $this->urlGenerator->generate(
                                        'front_suivi_signalement',
                                        [
                                            'code' => $entity->getSignalement()->getCodeSuivi(),
                                            'from' => $toRecipient,
                                        ],
                                        UrlGeneratorInterface::ABSOLUTE_URL
                                    ),
                                ],
                                $entity->getSignalement()->getTerritory()
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

    private function createInAppNotification($user, $entity, $type)
    {
        $notification = new Notification();
        $notification->setUser($user);
        switch ($type) {
            case Notification::TYPE_SUIVI:
                $notification->setSuivi($entity);
                $notification->setSignalement($entity->getSignalement());
                break;
            case Notification::TYPE_NEW_SIGNALEMENT:
                $notification->setSignalement($entity);
                break;
            default:
                $notification->setAffectation($entity);
                $notification->setSignalement($entity->getSignalement());
                break;
        }
        $notification->setType($type);
        $this->em->persist($notification);
        $this->uow->computeChangeSet(
            $this->em->getClassMetadata(Notification::class),
            $notification
        );
    }

    private function sendMail($entity, $mailType)
    {
        $options = [];
        $options['entity'] = $entity;
        $sendErrorMail = false;
        if ($entity instanceof Signalement) {
            /** @var Signalement $signalement */
            $signalement = $entity;
        } else {
            /** @var Signalement $signalement */
            $signalement = $entity->getSignalement();
        }
        if (!$this->tos->isEmpty()) {
            $uuid = $signalement->getUuid();
            $options = array_merge($options, [
                'link' => $this->urlGenerator->generate('back_signalement_view', [
                    'uuid' => $uuid,
                ], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

            $this->removeCurrentUserEmailForNotification();

            $this->tos = $this->tos->filter(function ($element) {
                return '' !== trim($element) && null !== $element;
            });

            if ($this->tos->isEmpty()) {
                $sendErrorMail = true;
            } else {
                $this->notifier->send($mailType, array_unique($this->tos->toArray()), $options, $signalement->getTerritory());
                $this->tos->clear();
            }
        } else {
            $sendErrorMail = true;
        }
        if ($sendErrorMail) {
            $this->notifier->send(
                NotificationService::TYPE_ERROR_SIGNALEMENT_NO_USER,
                $this->parameterBag->get('notifications_email'),
                [
                    'url' => $this->parameterBag->get('host_url'),
                    'error' => sprintf(
                        'Aucun utilisateur est notifiable pour le signalement #%s, notification prévue %s (TYPE_SIGNALEMENT_NEW = 3, TYPE_ASSIGNMENT_NEW = 4, TYPE_NEW_COMMENT_BACK = 10)',
                        $signalement->getReference(),
                        $mailType,
                    ),
                ],
                $signalement->getTerritory()
            );
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
            $args->getObjectManager()->flush();
        }
    }
}
