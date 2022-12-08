<?php

namespace App\Tests\Unit\Factory;

use App\Entity\Enum\MotifCloture;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Factory\SuiviFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class SuiviFactoryTest extends KernelTestCase
{
    private Security $security;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->security = $this->createMock(Security::class);
        $this->security->expects($this->once())->method('getUser')->willReturn(new User());
    }

    public function testCreateSuiviInstance(): void
    {
        $suiviFactory = new SuiviFactory();
        $signalement = $this->createMock(Signalement::class);

        $suivi = $suiviFactory->createInstanceFrom($this->security->getUser(), $signalement, []);

        $this->assertInstanceOf(Suivi::class, $suivi);
        $this->assertEquals('', $suivi->getDescription());
        $this->assertFalse($suivi->getIsPublic());
        $this->assertInstanceOf(UserInterface::class, $suivi->getCreatedBy());
    }

    public function testCreateSuiviInstanceWithClosedSignalementParameters(): void
    {
        $suiviFactory = new SuiviFactory();
        $signalement = $this->createMock(Signalement::class);
        $suivi = $suiviFactory->createInstanceFrom(
            $this->security->getUser(),
            $signalement, [
                'motif_suivi' => 'Lorem ipsum suivi sit amet, consectetur adipiscing elit.',
                'motif_cloture' => MotifCloture::LABEL['INSALUBRITE'],
                'subject' => 'tous les partenaires',
                'closed_for' => 'all',
            ],
            true
        );

        $this->assertInstanceOf(Suivi::class, $suivi);
        $this->assertTrue($suivi->getIsPublic());
        $this->assertInstanceOf(UserInterface::class, $suivi->getCreatedBy());
        $this->assertTrue(str_contains($suivi->getDescription(), MotifCloture::LABEL['INSALUBRITE']));
        $this->assertTrue(str_contains($suivi->getDescription(), 'Le signalement à été cloturé pour'));
    }
}
