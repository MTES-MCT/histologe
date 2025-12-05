<?php

namespace App\Tests\Functional\Manager;

use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\EventListener\SignalementUpdatedListener;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Manager\UserSignalementSubscriptionManager;
use App\Repository\UserRepository;
use App\Repository\UserSignalementSubscriptionRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SuiviManagerTest extends KernelTestCase
{
    private const string REF_SIGNALEMENT = '2022-8';
    private ManagerRegistry $managerRegistry;
    private SignalementUpdatedListener $signalementUpdatedListener;
    private EventDispatcherInterface $eventDispatcherInterface;
    private Security $security;
    private HtmlSanitizerInterface $htmlSanitizerInterface;
    private UserSignalementSubscriptionRepository $userSignalementSubscriptionRepository;
    private UserSignalementSubscriptionManager $userSignalementSubscriptionManager;
    private UserRepository $userRepository;
    private SuiviManager $suiviManager;
    private UserManager $userManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->managerRegistry = self::getContainer()->get(ManagerRegistry::class);
        $this->signalementUpdatedListener = static::getContainer()->get(SignalementUpdatedListener::class);
        $this->eventDispatcherInterface = static::getContainer()->get(EventDispatcherInterface::class);
        $this->security = static::getContainer()->get(Security::class);
        $this->htmlSanitizerInterface = self::getContainer()->get('html_sanitizer.sanitizer.app.message_sanitizer');
        $this->userSignalementSubscriptionManager = self::getContainer()->get(UserSignalementSubscriptionManager::class);
        $this->userRepository = self::getContainer()->get(UserRepository::class);
        $this->userSignalementSubscriptionRepository = self::getContainer()->get(UserSignalementSubscriptionRepository::class);
        $this->userManager = static::getContainer()->get(UserManager::class);
        $this->suiviManager = new SuiviManager(
            $this->managerRegistry,
            $this->signalementUpdatedListener,
            $this->eventDispatcherInterface,
            $this->security,
            $this->htmlSanitizerInterface,
            $this->userSignalementSubscriptionManager,
            $this->userManager,
            true,
            Suivi::class,
        );
    }

    public function testCreateSuivi(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->managerRegistry->getRepository(Signalement::class)->findOneBy(
            ['reference' => self::REF_SIGNALEMENT]
        );

        /** @var UserRepository $userRepository */
        $userRepository = $this->managerRegistry->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'user-13-03@signal-logement.fr']);

        $countSuivisBeforeCreate = $signalement->getSuivis()->count();
        $params = [
            'motif_suivi' => 'Lorem ipsum suivi sit amet, consectetur adipiscing elit.',
            'motif_cloture' => MotifCloture::tryFrom('NON_DECENCE'),
            'subject' => 'test',
        ];
        $suivi = $this->suiviManager->createSuivi(
            signalement : $signalement,
            description : SuiviManager::buildDescriptionClotureSignalement($params),
            type : Suivi::TYPE_PARTNER,
            category: SuiviCategory::SIGNALEMENT_IS_CLOSED,
            partner: $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory()),
            user : $user,
            isPublic : true,
        );
        $signalement->addSuivi($suivi);
        $countSuivisAfterCreate = $signalement->getSuivis()->count();

        $this->assertEquals(Suivi::TYPE_PARTNER, $suivi->getType());
        $this->assertNotEquals($countSuivisBeforeCreate, $countSuivisAfterCreate);
        $this->assertInstanceOf(Suivi::class, $suivi);
        $desc = Suivi::DESCRIPTION_MOTIF_CLOTURE_PARTNER.' test avec le motif suivant <br /><strong>Non décence</strong><br /><strong>Desc. : </strong>Lorem ipsum suivi sit amet, consectetur adipiscing elit.';
        $this->assertEquals($desc, $suivi->getDescription());
        $this->assertTrue($suivi->getIsPublic());
        $this->assertTrue($suivi->getIsSanitized());
        $this->assertInstanceOf(UserInterface::class, $suivi->getCreatedBy());
    }

    public function testCreateSuiviWithImageBase64(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->managerRegistry->getRepository(Signalement::class)->findOneBy(
            ['reference' => self::REF_SIGNALEMENT]
        );

        $desc = 'Salut ma poule <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAApgAAAKYB3X3/OAAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAANCSURBVEiJtZZPbBtFFMZ/M7ubXdtdb1xSFyeilBapySVU8h8OoFaooFSqiihIVIpQBKci6KEg9Q6H9kovIHoCIVQJJCKE1ENFjnAgcaSGC6rEnxBwA04Tx43t2FnvDAfjkNibxgHxnWb2e/u992bee7tCa00YFsffekFY+nUzFtjW0LrvjRXrCDIAaPLlW0nHL0SsZtVoaF98mLrx3pdhOqLtYPHChahZcYYO7KvPFxvRl5XPp1sN3adWiD1ZAqD6XYK1b/dvE5IWryTt2udLFedwc1+9kLp+vbbpoDh+6TklxBeAi9TL0taeWpdmZzQDry0AcO+jQ12RyohqqoYoo8RDwJrU+qXkjWtfi8Xxt58BdQuwQs9qC/afLwCw8tnQbqYAPsgxE1S6F3EAIXux2oQFKm0ihMsOF71dHYx+f3NND68ghCu1YIoePPQN1pGRABkJ6Bus96CutRZMydTl+TvuiRW1m3n0eDl0vRPcEysqdXn+jsQPsrHMquGeXEaY4Yk4wxWcY5V/9scqOMOVUFthatyTy8QyqwZ+kDURKoMWxNKr2EeqVKcTNOajqKoBgOE28U4tdQl5p5bwCw7BWquaZSzAPlwjlithJtp3pTImSqQRrb2Z8PHGigD4RZuNX6JYj6wj7O4TFLbCO/Mn/m8R+h6rYSUb3ekokRY6f/YukArN979jcW+V/S8g0eT/N3VN3kTqWbQ428m9/8k0P/1aIhF36PccEl6EhOcAUCrXKZXXWS3XKd2vc/TRBG9O5ELC17MmWubD2nKhUKZa26Ba2+D3P+4/MNCFwg59oWVeYhkzgN/JDR8deKBoD7Y+ljEjGZ0sosXVTvbc6RHirr2reNy1OXd6pJsQ+gqjk8VWFYmHrwBzW/n+uMPFiRwHB2I7ih8ciHFxIkd/3Omk5tCDV1t+2nNu5sxxpDFNx+huNhVT3/zMDz8usXC3ddaHBj1GHj/As08fwTS7Kt1HBTmyN29vdwAw+/wbwLVOJ3uAD1wi/dUH7Qei66PfyuRj4Ik9is+hglfbkbfR3cnZm7chlUWLdwmprtCohX4HUtlOcQjLYCu+fzGJH2QRKvP3UNz8bWk1qMxjGTOMThZ3kvgLI5AzFfo379UAAAAASUVORK5CYII=">';
        $descSanitized = 'Salut ma poule ';

        $suivi = $this->suiviManager->createSuivi(
            signalement : $signalement,
            description : $desc,
            type : Suivi::TYPE_USAGER,
            category: SuiviCategory::MESSAGE_USAGER,
        );
        $this->assertEquals($descSanitized, $suivi->getDescription());
    }

    public function testCreateSuiviWithAutoSubscription(): void
    {
        $signalement = $this->managerRegistry->getRepository(Signalement::class)->findOneBy(['uuid' => '00000000-0000-0000-2024-000000000008']);
        $user = $this->userRepository->findOneBy(['email' => 'admin-territoire-34-02@signal-logement.fr']);

        $this->suiviManager->createSuivi(
            signalement : $signalement,
            description : 'prise en charge du signalement',
            type : Suivi::TYPE_PARTNER,
            category: SuiviCategory::MESSAGE_PARTNER,
            partner: $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory()),
            user : $user,
        );

        $sub = $this->userSignalementSubscriptionRepository->findBy([
            'signalement' => $signalement,
            'user' => $user,
        ]);
        $this->assertCount(1, $sub);
    }
}
