<?php

namespace App\Tests\Functional\Manager;

use App\Entity\Enum\MotifCloture;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\EventListener\SignalementUpdatedListener;
use App\Manager\SuiviManager;
use App\Repository\DesordreCritereRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SuiviManagerTest extends KernelTestCase
{
    private const REF_SIGNALEMENT = '2022-8';
    private ManagerRegistry $managerRegistry;
    private SignalementUpdatedListener $signalementUpdatedListener;
    private Security $security;
    private UrlGeneratorInterface $urlGenerator;
    private DesordreCritereRepository $desordreCritereRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->managerRegistry = self::getContainer()->get(ManagerRegistry::class);
        $this->signalementUpdatedListener = static::getContainer()->get(SignalementUpdatedListener::class);
        $this->security = static::getContainer()->get(Security::class);
        $this->urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
        $this->desordreCritereRepository = static::getContainer()->get(DesordreCritereRepository::class);
    }

    public function testCreateSuivi(): void
    {
        $suiviManager = new SuiviManager(
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
        $params = [
            'motif_suivi' => 'Lorem ipsum suivi sit amet, consectetur adipiscing elit.',
            'motif_cloture' => MotifCloture::tryFrom('NON_DECENCE'),
            'subject' => 'test',
        ];
        $suivi = $suiviManager->createSuivi(
            user : $user,
            signalement : $signalement,
            description : SuiviManager::buildDescriptionClotureSignalement($params),
            type : Suivi::TYPE_PARTNER,
            isPublic : true,
        );
        $signalement->addSuivi($suivi);
        $countSuivisAfterCreate = $signalement->getSuivis()->count();

        $this->assertEquals(Suivi::TYPE_PARTNER, $suivi->getType());
        $this->assertNotEquals($countSuivisBeforeCreate, $countSuivisAfterCreate);
        $this->assertInstanceOf(Suivi::class, $suivi);
        $desc = 'Le signalement a été cloturé pour test avec le motif suivant <br><strong>Non décence</strong><br><strong>Desc. : </strong>Lorem ipsum suivi sit amet, consectetur adipiscing elit.';
        $this->assertEquals($desc, $suivi->getDescription());
        $this->assertTrue($suivi->getIsPublic());
        $this->assertInstanceOf(UserInterface::class, $suivi->getCreatedBy());
    }
}
