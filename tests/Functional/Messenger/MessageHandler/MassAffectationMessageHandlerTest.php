<?php

namespace App\Tests\Functional\Messenger\MessageHandler;

use App\Entity\Enum\NotificationType;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\User;
use App\Messenger\Message\MassAffectationMessage;
use App\Messenger\MessageHandler\MassAffectationMessageHandler;
use App\Repository\AffectationRepository;
use App\Repository\NotificationRepository;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MassAffectationMessageHandlerTest extends KernelTestCase
{
    private const string PARTNER_NAME = 'Partenaire 13-03';

    private MassAffectationMessageHandler $handler;
    private AffectationRepository $affectationRepository;
    private User $superAdmin;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->handler = $container->get(MassAffectationMessageHandler::class);
        $this->affectationRepository = $container->get(AffectationRepository::class);
        $this->superAdmin = $container->get(UserRepository::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);
    }

    public function testHandleMassAffectation(): void
    {
        $partner = $this->getPartner(self::PARTNER_NAME);
        $toAffect = $this->getSignalement('2022-4');            // actif, territoire 13, non affecté
        $alreadyAffected = $this->getSignalement('2022-8');     // actif, déjà affecté au partenaire
        $notActive = $this->getSignalement('2022-3');           // fermé
        $outOfPerimeter = $this->getSignalement('2024-08');     // actif, territoire 34

        $this->assertNull($toAffect->getAffectationForPartner($partner));
        $this->assertNotNull($alreadyAffected->getAffectationForPartner($partner));

        ($this->handler)(new MassAffectationMessage(
            partnerId: $partner->getId(),
            signalementIds: [$toAffect->getId(), $alreadyAffected->getId(), $notActive->getId(), $outOfPerimeter->getId()],
            userId: $this->superAdmin->getId(),
        ));

        // Affectations
        $affectation = $this->affectationRepository->findOneBy(['signalement' => $toAffect, 'partner' => $partner]);
        $this->assertNotNull($affectation);
        $this->assertSame($this->superAdmin, $affectation->getAffectedBy());
        $this->assertCount(1, $this->affectationRepository->findBy(['signalement' => $alreadyAffected, 'partner' => $partner]));
        $this->assertNull($this->affectationRepository->findOneBy(['signalement' => $notActive, 'partner' => $partner]));
        $this->assertNull($this->affectationRepository->findOneBy(['signalement' => $outOfPerimeter, 'partner' => $partner]));

        // Notifications in-app : uniquement pour l'agent actif du partenaire
        $notifications = static::getContainer()->get(NotificationRepository::class)->findBy([
            'affectation' => $affectation,
            'type' => NotificationType::NOUVELLE_AFFECTATION,
        ]);
        $this->assertCount(1, $notifications);
        $this->assertSame('user-13-02@signal-logement.fr', $notifications[0]->getUser()->getEmail());

        // Mails : récap partenaire + bilan super admin
        $this->assertEmailCount(2);

        $partnerEmail = $this->getMailerMessage(0);
        $this->assertEmailSubjectContains($partnerEmail, 'Un nouveau signalement vous attend');
        $this->assertEmailHtmlBodyContains($partnerEmail, '2022-4');
        $this->assertEmailHtmlBodyNotContains($partnerEmail, '2022-8');

        $reportEmail = $this->getMailerMessage(1);
        $this->assertEmailAddressContains($reportEmail, 'To', 'admin-01@signal-logement.fr');
        $this->assertEmailSubjectContains($reportEmail, 'Affectation en masse vers '.self::PARTNER_NAME.' terminée');
        $this->assertEmailHtmlBodyContains($reportEmail, 'Signalements affectés (1)');
        $this->assertEmailHtmlBodyContains($reportEmail, 'déjà affectés à ce partenaire (1)');
        $this->assertEmailHtmlBodyContains($reportEmail, 'hors du périmètre du partenaire (1)');
        $this->assertEmailHtmlBodyContains($reportEmail, 'ne sont plus en cours (1)');
        foreach (['2022-4', '2022-8', '2022-3', '2024-08'] as $reference) {
            $this->assertEmailHtmlBodyContains($reportEmail, '#'.$reference);
        }
    }

    public function testHandleWithNothingToAffect(): void
    {
        $partner = $this->getPartner(self::PARTNER_NAME);
        $alreadyAffected = $this->getSignalement('2022-8');
        $countBefore = \count($this->affectationRepository->findBy(['partner' => $partner]));

        ($this->handler)(new MassAffectationMessage(
            partnerId: $partner->getId(),
            signalementIds: [$alreadyAffected->getId()],
            userId: $this->superAdmin->getId(),
        ));

        $this->assertCount($countBefore, $this->affectationRepository->findBy(['partner' => $partner]));

        // Pas de mail au partenaire, seulement le bilan
        $this->assertEmailCount(1);
        $reportEmail = $this->getMailerMessage(0);
        $this->assertEmailSubjectContains($reportEmail, 'Affectation en masse vers');
        $this->assertEmailHtmlBodyContains($reportEmail, 'Aucun signalement n\'a été affecté.');
    }

    public function testHandleArchivedPartner(): void
    {
        $partner = $this->getPartner('Partenaire 13-07');
        $this->assertTrue($partner->getIsArchive());
        $signalement = $this->getSignalement('2022-4');

        ($this->handler)(new MassAffectationMessage(
            partnerId: $partner->getId(),
            signalementIds: [$signalement->getId()],
            userId: $this->superAdmin->getId(),
        ));

        $this->assertNull($this->affectationRepository->findOneBy(['signalement' => $signalement, 'partner' => $partner]));
        $this->assertEmailCount(0);
    }

    private function getPartner(string $name): Partner
    {
        /** @var Partner $partner */
        $partner = static::getContainer()->get(PartnerRepository::class)->findOneBy(['nom' => $name]);

        return $partner;
    }

    private function getSignalement(string $reference): Signalement
    {
        /** @var Signalement $signalement */
        $signalement = static::getContainer()->get(SignalementRepository::class)->findOneBy(['reference' => $reference]);

        return $signalement;
    }
}
