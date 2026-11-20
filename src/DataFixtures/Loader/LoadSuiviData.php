<?php

namespace App\DataFixtures\Loader;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Event\SuiviCreatedEvent;
use App\Manager\SuiviManager;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Yaml\Yaml;

class LoadSuiviData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(
        private readonly SignalementRepository $signalementRepository,
        private readonly UserRepository $userRepository,
        private readonly ParameterBagInterface $parameterBag,
        private readonly SuiviManager $suiviManager,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function load(ObjectManager $manager): void
    {
        $signalements = $this->signalementRepository->findBy(['statut' => [SignalementStatus::ACTIVE->value, SignalementStatus::CLOSED->value]]);
        $admin = $this->userRepository->findOneBy(['email' => $this->parameterBag->get('user_system_email')]);
        if (!$admin) {
            throw new \UnexpectedValueException(sprintf('Admin user with email %s not found', $this->parameterBag->get('user_system_email')));
        }

        $second = 1;
        foreach ($signalements as $signalement) {
            /** @var int $territoryId */
            $territoryId = $signalement->getAddress()->getTerritory()->getId();
            $rt = $this->userRepository->findActiveTerritoryAdmins($territoryId);
            $user = $rt ? $rt[0] : $admin;
            $suivi = $this->suiviManager->createSuivi(
                signalement: $signalement,
                description: '',
                category: SuiviCategory::SIGNALEMENT_IS_ACTIVE,
                partner: $user->getPartnerInTerritoryOrFirstOne($signalement->getAddress()->getTerritory()),
                user: $user,
                isVisibleForUsager: true,
            );
            $createdAtUpdated = $signalement->getCreatedAt()->modify('+'.$second.' second');
            $suivi->setCreatedAt($createdAtUpdated);
            ++$second;
        }
        $manager->flush();

        $suiviRows = Yaml::parseFile(__DIR__.'/../Files/Suivi.yml');
        foreach ($suiviRows['suivis'] as $row) {
            $this->loadSuivi($manager, $row);
        }
        $manager->flush();
    }

    /**
     * @param array<string, mixed> $row
     *
     * @throws \Exception
     */
    public function loadSuivi(ObjectManager $manager, array $row): void
    {
        $signalement = $this->signalementRepository->findOneBy(['reference' => $row['signalement']]);
        if (!$signalement) {
            $signalement = $this->signalementRepository->findOneBy(['uuid' => $row['signalement']]);
            if (!$signalement) {
                throw new \UnexpectedValueException(sprintf('Signalement with reference or UUID %s not found', $row['signalement']));
            }
        }
        $createdBy = isset($row['created_by']) ? $this->userRepository->findOneBy(['email' => $row['created_by']]) : null;
        // si le suivi est créé par un utilisateur API, on récupère le partenaire de l'affectation du signalement
        // sinon on récupère le partenaire de l'utilisateur
        $partner = $createdBy ?
            (
                $createdBy->isApiUser() ?
                ($signalement->getAffectations()->first() ?: null)?->getPartner() :
                $createdBy->getPartnerInTerritoryOrFirstOneOrNull($signalement->getAddress()->getTerritory())
            ) :
            null;
        $createdAt = new \DateTimeImmutable();
        if (isset($row['created_at'])) {
            $createdAt = new \DateTimeImmutable($row['created_at']);
        } elseif (SuiviCategory::MESSAGE_USAGER_POST_CLOTURE->value === $row['category']) {
            if (null === $signalement->getClosedAt()) {
                throw new \UnexpectedValueException(sprintf('Signalement with reference %s has no closed date', $row['signalement']));
            }
            $createdAt = $signalement->getClosedAt()->modify('+3 days');
        }
        $category = SuiviCategory::from($row['category']);

        $suivi = $this->suiviManager->createSuivi(
            signalement: $signalement,
            description: $row['description'],
            category: $category,
            sendMail: SuiviCategory::ASK_FEEDBACK_SENT !== $category,
            partner: $partner,
            user: $createdBy,
            isVisibleForUsager: $row['is_public'],
            isVisibleForBailleur: in_array($category, SuiviCategory::categoriesSubmittedByBailleur()),
            createdAt: $createdAt,
        );
        if (isset($row['force_notifications']) && $row['force_notifications']) {
            $suivi->setWaitingNotification(false);
            $this->eventDispatcher->dispatch(new SuiviCreatedEvent($suivi), SuiviCreatedEvent::NAME); // @phpstan-ignore-line
        }
    }

    public function getOrder(): int
    {
        return 15;
    }
}
