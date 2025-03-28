<?php

namespace App\Tests\Functional\Manager;

use App\Entity\Partner;
use App\Entity\PopNotification;
use App\Entity\User;
use App\Manager\PopNotificationManager;
use App\Repository\PartnerRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PopNotificationManagerTest extends KernelTestCase
{
    private ManagerRegistry $managerRegistry;
    private PopNotificationManager $popNotificationManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->managerRegistry = self::getContainer()->get(ManagerRegistry::class);
        $this->popNotificationManager = new PopNotificationManager(
            $this->managerRegistry,
            PopNotification::class,
        );
    }

    public function testCreateOrUpdatePopNotification(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->managerRegistry->getRepository(User::class);
        /** @var PartnerRepository $partnerRepository */
        $partnerRepository = $this->managerRegistry->getRepository(Partner::class);

        $user = $userRepository->findOneBy(['email' => 'user-13-01@signal-logement.fr']);
        $addPartners = [];
        // ajout d'un partenaire
        $partner63 = $partnerRepository->findOneBy(['nom' => 'ADIL 63']);
        $popNotification = $this->popNotificationManager->createOrUpdatePopNotification($user, 'addPartner', $partner63);
        $addPartners[] = $partner63->getId();
        $this->assertEquals($addPartners, $popNotification->getParams()['addedPartners']);
        // ajout d'un autre partenaire
        $partner89 = $partnerRepository->findOneBy(['nom' => 'ADIL 89']);
        $addPartners[] = $partner89->getId();
        $popNotification = $this->popNotificationManager->createOrUpdatePopNotification($user, 'addPartner', $partner89);
        $this->assertEquals($addPartners, $popNotification->getParams()['addedPartners']);
        // suppression du premier partenaire
        $popNotification = $this->popNotificationManager->createOrUpdatePopNotification($user, 'removePartner', $partner63);
        unset($addPartners[array_search($partner63->getId(), $addPartners)]);
        $this->assertEquals($addPartners, $popNotification->getParams()['addedPartners']);
        // suppression du second partenaire
        $popNotification = $this->popNotificationManager->createOrUpdatePopNotification($user, 'removePartner', $partner89);
        $this->assertNull($popNotification);
    }
}
