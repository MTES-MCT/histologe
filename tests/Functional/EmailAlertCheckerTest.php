<?php

namespace App\Tests\Functional;

use App\Repository\SignalementRepository;
use App\Service\EmailAlertChecker;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EmailAlertCheckerTest extends KernelTestCase
{
    private EmailAlertChecker $emailAlertChecker;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        /** @var EmailAlertChecker $emailAlertChecker */
        $emailAlertChecker = static::getContainer()->get(EmailAlertChecker::class);
        $this->emailAlertChecker = $emailAlertChecker;
    }

    public function testHasUsagerEmailAlert(): void
    {
        $hasUsagerEmailAlert = $this->emailAlertChecker->hasUsagerEmailAlert(
            'occupant',
            'nawell.mapaire@yopmail.com'
        );
        $this->assertTrue($hasUsagerEmailAlert);
    }

    public function testHasPartnerEmailAlert(): void
    {
        $hasPartnerEmailAlert = $this->emailAlertChecker->hasPartnerEmailAlert('partenaire-13-01@signal-logement.fr');
        $this->assertTrue($hasPartnerEmailAlert);
    }

    public function testBuildPartnerEmailAlert(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $signalement = $signalementRepository->findOneBy(['reference' => '2024-10']);
        $partnerEmailAlerts = $this->emailAlertChecker->buildPartnerEmailAlert($signalement);

        $this->assertArrayHasKey('7', $partnerEmailAlerts, 'Partenaire 13-06 ESABORA ARS doit être affecté au signalement #2024-10');
        $this->assertArrayHasKey('90', $partnerEmailAlerts, 'Partenaire 13-10 EPCI doit être affecté au signalement #2024-10');
        $this->assertFalse($partnerEmailAlerts[7], 'Partenaire 13-06 ESABORA ARS ne doit pas avoir le badge.');
        $this->assertTrue($partnerEmailAlerts[90], 'Partenaire 13-10 EPCI doit avoir le badge.');
    }
}
