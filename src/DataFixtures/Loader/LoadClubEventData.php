<?php

namespace App\DataFixtures\Loader;

use App\Entity\ClubEvent;
use App\Entity\Enum\PartnerType;
use App\Repository\TerritoryRepository;
use App\Service\TimezoneProvider;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadClubEventData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(
        private TerritoryRepository $territoryRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $clubEvents = Yaml::parseFile(__DIR__.'/../Files/ClubEvent.yml');
        foreach ($clubEvents['club_events'] as $row) {
            $this->loadClubEvent($manager, $row);
        }
        $manager->flush();
    }

    /**
     * @param array<string, mixed> $row
     */
    public function loadClubEvent(ObjectManager $manager, array $row): void
    {
        $clubEvent = (new ClubEvent())
            ->setName($row['name'])
            ->setUrl($row['url'])
        ;
        $utcDate = new \DateTimeImmutable(
            $row['date_event'],
            new \DateTimeZone(TimezoneProvider::TIMEZONE_EUROPE_PARIS)
        );
        $utcDate = $utcDate->setTimezone(new \DateTimeZone('UTC'));
        $clubEvent->setDateEvent($utcDate);
        if (isset($row['user_roles'])) {
            $clubEvent->setUserRoles($row['user_roles']);
        }
        if (isset($row['partner_types'])) {
            $clubEvent->setPartnerTypes(array_map(fn ($type) => PartnerType::tryFrom($type), $row['partner_types']));
        }
        $manager->persist($clubEvent);
    }

    public function getOrder(): int
    {
        return 24;
    }
}
