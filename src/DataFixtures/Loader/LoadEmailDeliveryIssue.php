<?php

namespace App\DataFixtures\Loader;

use App\Entity\EmailDeliveryIssue;
use App\Entity\Enum\BrevoEvent;
use App\Repository\PartnerRepository;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadEmailDeliveryIssue extends Fixture implements OrderedFixtureInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PartnerRepository $partnerRepository,
    ) {
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
        $user = $this->userRepository->findOneBy(['email' => $row['email']]);
        if (!$user) {
            $partner = $this->partnerRepository->findOneBy(['email' => $row['email']]);
        }

        $emailDeliveryIssue = (new EmailDeliveryIssue())
            ->setEmail($row['email'])
            ->setEvent(BrevoEvent::from($row['event']))
            ->setReason($row['reason'])
            ->setPayload($row['payload']);

        if ($user) {
            $user->setEmailDeliveryIssue($emailDeliveryIssue);
        } else {
            $partner->setEmailDeliveryIssue($emailDeliveryIssue);
        }
        $manager->persist($emailDeliveryIssue);
    }

    public function getOrder(): int
    {
        return 25;
    }
}
