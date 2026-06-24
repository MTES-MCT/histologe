<?php

namespace App\Tests\Functional\Messenger\MessageHandler;

use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Messenger\Message\SignalementDraftProcessMessage;
use App\Messenger\MessageHandler\HistoriqueEvenementsMessageHandler;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class HistoriqueEvenementsMessageHandlerTest extends KernelTestCase
{
    public function testInvokeWithSignalementsAtSameAddress(): void
    {
        $_ENV['FEATURE_HISTO_ADDRESS'] = '1';
        self::bootKernel();
        $container = static::getContainer();
        $signalementRepository = $container->get(SignalementRepository::class);
        $suiviRepository = $container->get(SuiviRepository::class);
        $handler = $container->get(HistoriqueEvenementsMessageHandler::class);

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2022-1']);
        $this->assertNotNull($signalement);

        $initialSuivis = $suiviRepository->findBy([
            'signalement' => $signalement,
            'category' => SuiviCategory::SIGNALEMENT_HISTORIQUE_EVENEMENT,
        ]);

        $initialCount = \count($initialSuivis);

        $message = new SignalementDraftProcessMessage($signalement->getCreatedFrom()?->getId(), $signalement->getId());
        $handler($message);
        $container->get('doctrine')->getManager()->flush();

        $finalSuivis = $suiviRepository->findBy([
            'signalement' => $signalement,
            'category' => SuiviCategory::SIGNALEMENT_HISTORIQUE_EVENEMENT,
        ]);

        $this->assertCount($initialCount + 1, $finalSuivis);

        foreach ($finalSuivis as $suivi) {
            $this->assertStringContainsString('historique des', $suivi->getDescription());
            $this->assertStringContainsString('évènements', $suivi->getDescription());
        }
    }

    protected function tearDown(): void
    {
        $_ENV['FEATURE_HISTO_ADDRESS'] = '0';
        parent::tearDown();
    }
}
