<?php

namespace App\Tests\Unit\Service\Signalement\Suivi;

use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Service\Signalement\Suivi\SuiviMentionExtractor;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SuiviMentionExtractorTest extends KernelTestCase
{
    private readonly EntityManagerInterface $entityManager;
    private readonly SuiviMentionExtractor $suiviMentionExtractor;
    private readonly PartnerRepository $partnerRepository;
    private readonly SignalementRepository $signalementRepository;
    private readonly Signalement $signalement;
    private readonly Partner $partner3;
    private readonly Partner $partner4;
    private readonly string $description;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $this->entityManager = $entityManager;
        $this->partnerRepository = $this->entityManager->getRepository(Partner::class);
        $this->signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $this->suiviMentionExtractor = new SuiviMentionExtractor($this->partnerRepository);
        $this->signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2022-000000000010']);
        $this->partner3 = $this->partnerRepository->findOneBy(['nom' => 'Partenaire 13-02']);
        $this->partner4 = $this->partnerRepository->findOneBy(['nom' => 'Partenaire 13-03']);
        $this->description = 'Merci de vérifier avec <span class="text-mentioned" data-partner-id="'.$this->partner3->getId().'">@'.$this->partner3->getNom().'</span>';
    }

    public function testExtractFromDescription(): void
    {
        $suivi = (new Suivi())
            ->setDescription(
                $this->description
                .' et <span class="text-mentioned" data-partner-id="'.$this->partner4->getId().'">@'.$this->partner4->getNom().'</span>,'
                .' merci <span class="text-mentioned" data-partner-id="'.$this->partner3->getId().'">@'.$this->partner3->getNom().'</span>'
            )
            ->setType(Suivi::TYPE_PARTNER)
            ->setSignalement($this->signalement)
            ->setCategory(SuiviCategory::MESSAGE_PARTNER);

        // dédoublonné, peu importe le nombre de fois où un même partenaire est mentionné
        $this->assertEqualsCanonicalizing([$this->partner3, $this->partner4], $this->suiviMentionExtractor->extract($suivi));
    }

    public function testExtractReturnsEmptyWhenNoMention(): void
    {
        $suivi = (new Suivi())
            ->setDescription('Un suivi sans aucune mention de partenaire')
            ->setType(Suivi::TYPE_PARTNER)
            ->setSignalement($this->signalement)
            ->setCategory(SuiviCategory::MESSAGE_PARTNER);

        $this->assertSame([], $this->suiviMentionExtractor->extract($suivi));
    }

    public function testExtractReturnsEmptyWhenCategoryDifferent(): void
    {
        $suivi = (new Suivi())
            ->setDescription($this->description)
            ->setType(Suivi::TYPE_PARTNER)
            ->setSignalement($this->signalement)
            ->setCategory(SuiviCategory::MESSAGE_ESABORA_SCHS);

        $this->assertSame([], $this->suiviMentionExtractor->extract($suivi));
    }

    public function testExtractReturnsEmptyWhenVisibleForUsagerOrBailleur(): void
    {
        $suivi = (new Suivi())
            ->setDescription($this->description)
            ->setType(Suivi::TYPE_PARTNER)
            ->setIsVisibleForUsager(true)
            ->setSignalement($this->signalement)
            ->setCategory(SuiviCategory::MESSAGE_PARTNER);
        $this->assertSame([], $this->suiviMentionExtractor->extract($suivi));

        $suivi = (new Suivi())
            ->setDescription($this->description)
            ->setType(Suivi::TYPE_PARTNER)
            ->setIsVisibleForBailleur(true)
            ->setSignalement($this->signalement)
            ->setCategory(SuiviCategory::MESSAGE_PARTNER);
        $this->assertSame([], $this->suiviMentionExtractor->extract($suivi));
    }

    public function testExtractReturnsEmptyWhenNoAffectation(): void
    {
        $partner = $this->partnerRepository->findOneBy(['nom' => 'Partenaire 63-01']);
        $suivi = (new Suivi())
            ->setDescription(
                'Merci de vérifier avec <span class="text-mentioned" data-partner-id="'.$partner->getId().'">@'.$partner->getNom().'</span>'
            )
            ->setType(Suivi::TYPE_PARTNER)
            ->setSignalement($this->signalement)
            ->setCategory(SuiviCategory::MESSAGE_PARTNER);

        $this->assertSame([], $this->suiviMentionExtractor->extract($suivi));
    }

    public function testExtractReturnsEmptyWhenAffectationClosed(): void
    {
        $suivi = (new Suivi())
            ->setDescription($this->description)
            ->setType(Suivi::TYPE_PARTNER)
            ->setSignalement($this->signalement)
            ->setCategory(SuiviCategory::MESSAGE_PARTNER);

        $affectation = $this->signalement->getAffectationForPartner($this->partner3);
        $affectation->setStatut(AffectationStatus::CLOSED);
        $this->assertSame([], $this->suiviMentionExtractor->extract($suivi));
    }
}
