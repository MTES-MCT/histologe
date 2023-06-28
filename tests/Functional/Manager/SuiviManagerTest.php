<?php

namespace App\Tests\Functional\Manager;

use App\Entity\Enum\MotifCloture;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Factory\SuiviFactory;
use App\Manager\SuiviManager;
use App\Repository\SuiviRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SuiviManagerTest extends KernelTestCase
{
    private const REF_SIGNALEMENT = '2022-8';
    private ManagerRegistry $managerRegistry;
    private SuiviFactory $suiviFactory;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->managerRegistry = self::getContainer()->get(ManagerRegistry::class);
        $this->suiviFactory = static::getContainer()->get(SuiviFactory::class);
    }

    public function testCreateSuivi(): void
    {
        $suiviManager = new SuiviManager(
            $this->suiviFactory,
            $this->managerRegistry,
            Suivi::class,
        );

        /** @var Signalement $signalement */
        $signalement = $this->managerRegistry->getRepository(Signalement::class)->findOneBy(
            ['reference' => self::REF_SIGNALEMENT]
        );

        /** @var UserRepository $userRepository */
        $userRepository = $this->managerRegistry->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'user-13-03@histologe.fr']);

        $countSuivisBeforeCreate = $signalement->getSuivis()->count();
        $suivi = $suiviManager->createSuivi($user, $signalement, [
                    'motif_suivi' => 'Lorem ipsum suivi sit amet, consectetur adipiscing elit.',
                    'motif_cloture' => MotifCloture::tryFrom('NON_DECENCE'),
                    'subject' => 'test',
                ], true);

        $suiviManager->save($suivi);
        $signalement->addSuivi($suivi);
        $countSuivisAfterCreate = $signalement->getSuivis()->count();

        $this->assertEquals(Suivi::TYPE_PARTNER, $suivi->getType());
        $this->assertNotEquals($countSuivisBeforeCreate, $countSuivisAfterCreate);
    }

    public function testUpdateSuiviCreatedByUser(): void
    {
        $suiviManager = new SuiviManager(
            $this->suiviFactory,
            $this->managerRegistry,
            Suivi::class,
        );

        /** @var SuiviRepository $suiviRepository */
        $suiviRepository = $this->managerRegistry->getRepository(Suivi::class);
        /** @var Suivi $suivi */
        $suivi = $suiviRepository->findOneBy(['createdBy' => null]);

        /** @var UserRepository $userRepository */
        $userRepository = $this->managerRegistry->getRepository(User::class);
        /** @var User $user */
        $user = $userRepository->findOneBy(['email' => 'user-13-03@histologe.fr']);

        $suiviManager->updateSuiviCreatedByUser($suivi, $user);
        $userAffected = $suivi->getCreatedBy();

        $this->assertNotNull($userAffected);
    }
}
