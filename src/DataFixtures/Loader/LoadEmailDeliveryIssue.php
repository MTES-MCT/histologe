<?php

namespace App\DataFixtures\Loader;

use App\Entity\EmailDeliveryIssue;
use App\Entity\Enum\BrevoEvent;
use App\Repository\PartnerRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadEmailDeliveryIssue extends Fixture implements OrderedFixtureInterface
{
    public function __construct(private readonly PartnerRepository $partnerRepository)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $emailDeliveryIssueRows = Yaml::parseFile(__DIR__.'/../Files/EmailDeliveryIssue.yml');
        foreach ($emailDeliveryIssueRows['email_delivery_issue'] as $row) {
            $this->loadEmailDeliveryIssue($manager, $row);
        }
        $manager->flush();
    }

    /**
     * @param array<string, mixed> $row
     */
    public function loadEmailDeliveryIssue(ObjectManager $manager, array $row): void
    {
        $emailDeliveryIssue = (new EmailDeliveryIssue())
            ->setEmail($row['email'])
            ->setEvent(BrevoEvent::from($row['event']))
            ->setReason($row['reason'])
            ->setPayload($row['payload']);

        $manager->persist($emailDeliveryIssue);
    }

    public function getOrder(): int
    {
        return 25;
    }
}
