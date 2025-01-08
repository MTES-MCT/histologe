<?php

namespace App\Tests\Unit\Factory;

use App\Entity\Enum\MotifCloture;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Factory\SuiviFactory;
use App\Repository\DesordreCritereRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SuiviFactoryTest extends KernelTestCase
{
    private MockObject|Security $security;
    private UrlGeneratorInterface $urlGenerator;
    private DesordreCritereRepository $desordreCritereRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->security = $this->createMock(Security::class);
        $this->security->expects($this->once())->method('getUser')->willReturn(new User());
        $this->urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
        $this->desordreCritereRepository = static::getContainer()->get(DesordreCritereRepository::class);
    }

    public function testCreateSuiviInstance(): void
    {
        $suiviFactory = new SuiviFactory($this->urlGenerator, $this->desordreCritereRepository);
        /** @var MockObject&Signalement $signalement */
        $signalement = $this->createMock(Signalement::class);
        /** @var User $user */
        $user = $this->security->getUser();

        $suivi = $suiviFactory->createInstanceFrom(
            user: $this->security->getUser(),
            signalement: $signalement,
            description: '',
            type: Suivi::TYPE_PARTNER,
        );

        $this->assertInstanceOf(Suivi::class, $suivi);
        $this->assertEquals('', $suivi->getDescription());
        $this->assertFalse($suivi->getIsPublic());
        $this->assertInstanceOf(UserInterface::class, $suivi->getCreatedBy());
    }

    public function testCreateSuiviInstanceWithClosedSignalementParameters(): void
    {
        $suiviFactory = new SuiviFactory($this->urlGenerator, $this->desordreCritereRepository);
        /** @var MockObject&Signalement $signalement */
        $signalement = $this->createMock(Signalement::class);
        $params = [
            'motif_suivi' => 'Lorem ipsum suivi sit amet, consectetur adipiscing elit.',
            'motif_cloture' => MotifCloture::tryFrom('INSALUBRITE'),
            'subject' => 'tous les partenaires',
            'closed_for' => 'all',
        ];
        $suivi = $suiviFactory->createInstanceFrom(
            user: $this->security->getUser(),
            signalement: $signalement,
            description: $suiviFactory->buildDescriptionClotureSignalement($params),
            type: Suivi::TYPE_PARTNER,
            isPublic: true,
        );

        $this->assertInstanceOf(Suivi::class, $suivi);
        $this->assertTrue($suivi->getIsPublic());
        $this->assertEquals(Suivi::TYPE_PARTNER, $suivi->getType());
        $this->assertInstanceOf(UserInterface::class, $suivi->getCreatedBy());
        $this->assertTrue(str_contains($suivi->getDescription(), MotifCloture::tryFrom('INSALUBRITE')->label()));
        $this->assertTrue(str_contains($suivi->getDescription(), 'Le signalement a été cloturé pour'));
    }
}
