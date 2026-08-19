<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SuiviRepositoryTest extends KernelTestCase
{
    private SuiviRepository $suiviRepository;

    private EntityManagerInterface $entityManager;

    public const USER_ADMIN = 'admin-01@signal-logement.fr';

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $this->entityManager = $entityManager;
        $this->suiviRepository = $this->entityManager->getRepository(Suivi::class);
    }

    public function testFindSignalementsForFirstAskFeedbackRelance(): void
    {
        $result = $this->suiviRepository->findSignalementsForFirstAskFeedbackRelance();
        $this->assertCount(6, $result);
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        for ($i = 0; $i < count($result); ++$i) {
            $signalement = $signalementRepository->findOneBy(['id' => $result[$i]]);
            $this->assertContains($signalement->getReference(), ['2023-13', '2023-19', '2023-20', '2023-120', '2024-01', '2024-02']);
        }
    }

    public function testFindSignalementsForSecondAskFeedbackRelance(): void
    {
        $result = $this->suiviRepository->findSignalementsForSecondAskFeedbackRelance();
        $this->assertCount(1, $result);
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalement = $signalementRepository->findOneBy(['id' => $result[0]]);
        $this->assertEquals('2023-14', $signalement->getReference());
    }

    public function testFindSignalementsForThirdAskFeedbackRelance(): void
    {
        $result = $this->suiviRepository->findSignalementsForThirdAskFeedbackRelance();
        $this->assertCount(1, $result);
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalement = $signalementRepository->findOneBy(['id' => $result[0]]);
        $this->assertEquals('2023-15', $signalement->getReference());
    }

    public function testFindSignalementsForLoopAskFeedbackRelance(): void
    {
        $result = $this->suiviRepository->findSignalementsForLoopAskFeedbackRelance();
        $this->assertCount(1, $result);
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalement = $signalementRepository->findOneBy(['id' => $result[0]]);
        $this->assertEquals('2022-8', $signalement->getReference());
    }

    public function testFindSignalementsForFirstAskFeedbackRelanceExcludesSignalementAlreadyInLoop(): void
    {
        // 2022-8 a déjà 3 ASK_FEEDBACK_SENT (fixture) : il est déjà en phase boucle 90 jours.
        // Un nouveau suivi public, même vieux de plus de 45 jours, ne doit plus le faire
        // repasser par la 1ère relance.
        $signalement = $this->getSignalementByReference('2022-8');
        $this->addSuivi($signalement, SuiviCategory::MESSAGE_PARTNER, new \DateTimeImmutable('-50 days'), isVisibleForUsager: true);

        $result = $this->suiviRepository->findSignalementsForFirstAskFeedbackRelance();
        $this->assertNotContains($signalement->getId(), array_map('intval', $result));
    }

    public function testFindSignalementsForSecondAskFeedbackRelanceExcludesSignalementAlreadyInLoop(): void
    {
        $signalement = $this->getSignalementByReference('2022-8');
        $this->addSuivi($signalement, SuiviCategory::MESSAGE_PARTNER, new \DateTimeImmutable('-80 days'), isVisibleForUsager: true);
        $this->addSuivi($signalement, SuiviCategory::ASK_FEEDBACK_SENT, new \DateTimeImmutable('-35 days'));

        $result = $this->suiviRepository->findSignalementsForSecondAskFeedbackRelance();
        $this->assertNotContains($signalement->getId(), array_map('intval', $result));
    }

    public function testFindSignalementsForThirdAskFeedbackRelanceExcludesSignalementAlreadyInLoop(): void
    {
        $signalement = $this->getSignalementByReference('2022-8');
        $this->addSuivi($signalement, SuiviCategory::MESSAGE_PARTNER, new \DateTimeImmutable('-80 days'), isVisibleForUsager: true);
        $this->addSuivi($signalement, SuiviCategory::ASK_FEEDBACK_SENT, new \DateTimeImmutable('-50 days'));
        $this->addSuivi($signalement, SuiviCategory::ASK_FEEDBACK_SENT, new \DateTimeImmutable('-35 days'));

        $result = $this->suiviRepository->findSignalementsForThirdAskFeedbackRelance();
        $this->assertNotContains($signalement->getId(), array_map('intval', $result));
    }

    public function testFindSignalementsForLoopAskFeedbackRelanceStaysInLoopAfterNewPublicSuivi(): void
    {
        // Le minuteur de 90 jours redémarre depuis le nouveau suivi public, mais le dossier
        // reste en mode boucle : il ne doit plus jamais repasser par la 1ère relance.
        $signalement = $this->getSignalementByReference('2022-8');
        $this->addSuivi($signalement, SuiviCategory::MESSAGE_PARTNER, new \DateTimeImmutable('-95 days'), isVisibleForUsager: true);

        $loopResult = $this->suiviRepository->findSignalementsForLoopAskFeedbackRelance();
        $this->assertContains($signalement->getId(), array_map('intval', $loopResult));

        $firstResult = $this->suiviRepository->findSignalementsForFirstAskFeedbackRelance();
        $this->assertNotContains($signalement->getId(), array_map('intval', $firstResult));
    }

    public function testFindSignalementsForLoopAskFeedbackRelanceNotYetEligibleAfterRecentPublicSuivi(): void
    {
        // Le nouveau suivi public a moins de 90 jours : le dossier ne doit pas encore
        // redéclencher de mail, ni boucle, ni 1ère relance.
        $signalement = $this->getSignalementByReference('2022-8');
        $this->addSuivi($signalement, SuiviCategory::MESSAGE_PARTNER, new \DateTimeImmutable('-10 days'), isVisibleForUsager: true);

        $loopResult = $this->suiviRepository->findSignalementsForLoopAskFeedbackRelance();
        $this->assertNotContains($signalement->getId(), array_map('intval', $loopResult));

        $firstResult = $this->suiviRepository->findSignalementsForFirstAskFeedbackRelance();
        $this->assertNotContains($signalement->getId(), array_map('intval', $firstResult));
    }

    private function getSignalementByReference(string $reference): Signalement
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalement = $signalementRepository->findOneBy(['reference' => $reference]);
        $this->assertNotNull($signalement);

        return $signalement;
    }

    private function addSuivi(
        Signalement $signalement,
        SuiviCategory $category,
        \DateTimeImmutable $createdAt,
        bool $isVisibleForUsager = false,
    ): void {
        $suivi = (new Suivi())
            ->setSignalement($signalement)
            ->setDescription('')
            ->setCategory($category)
            ->setType(SuiviCategory::getSuiviTypeForSuiviCategory($category))
            ->setIsVisibleForUsager($isVisibleForUsager)
            ->setCreatedAt($createdAt);
        $this->entityManager->persist($suivi);
        $this->entityManager->flush();
    }
}
