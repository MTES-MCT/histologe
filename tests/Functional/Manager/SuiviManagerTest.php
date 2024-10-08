<?php

namespace App\Tests\Functional\Manager;

use App\Entity\Enum\MotifCloture;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\EventListener\SignalementUpdatedListener;
use App\Factory\SuiviFactory;
use App\Manager\SuiviManager;
use App\Repository\DesordreCritereRepository;
use App\Repository\SuiviRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SuiviManagerTest extends KernelTestCase
{
    private const REF_SIGNALEMENT = '2022-8';
    private ManagerRegistry $managerRegistry;
    private SuiviFactory $suiviFactory;
    private UrlGeneratorInterface $urlGenerator;
    private SignalementUpdatedListener $signalementUpdatedListener;
    private Security $security;
    private DesordreCritereRepository $desordreCritereRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->managerRegistry = self::getContainer()->get(ManagerRegistry::class);
        $this->suiviFactory = static::getContainer()->get(SuiviFactory::class);
        $this->urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
        $this->signalementUpdatedListener = static::getContainer()->get(SignalementUpdatedListener::class);
        $this->security = static::getContainer()->get(Security::class);
        $this->desordreCritereRepository = static::getContainer()->get(DesordreCritereRepository::class);
    }

    public function testCreateSuivi(): void
    {
        $suiviManager = new SuiviManager(
            $this->suiviFactory,
            $this->managerRegistry,
            $this->urlGenerator,
            $this->signalementUpdatedListener,
            $this->security,
            $this->desordreCritereRepository,
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
            $this->urlGenerator,
            $this->signalementUpdatedListener,
            $this->security,
            $this->desordreCritereRepository,
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
