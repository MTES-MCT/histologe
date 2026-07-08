<?php

namespace App\Tests\Unit\Service\Signalement\Suivi;

use App\Entity\Enum\ArreteType;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\User;
use App\Manager\SuiviManager;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Service\Signalement\SignalementSameAddressArreteFinder;
use App\Service\Signalement\Suivi\HistoriqueEvenementsGenerator;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HistoriqueEvenementsGeneratorTest extends TestCase
{
    use FixturesHelper;

    private MockObject&SignalementRepository $signalementRepository;
    private MockObject&SignalementSameAddressArreteFinder $arreteFinder;
    private MockObject&SuiviManager $suiviManager;
    private MockObject&UserRepository $userRepository;
    private MockObject&ParameterBagInterface $parameterBag;
    private MockObject&UrlGeneratorInterface $urlGenerator;
    private HistoriqueEvenementsGenerator $historiqueEvenementsGenerator;

    protected function setUp(): void
    {
        $this->signalementRepository = $this->createMock(SignalementRepository::class);
        $this->arreteFinder = $this->createMock(SignalementSameAddressArreteFinder::class);
        $this->suiviManager = $this->createMock(SuiviManager::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $this->historiqueEvenementsGenerator = new HistoriqueEvenementsGenerator(
            $this->signalementRepository,
            $this->arreteFinder,
            $this->suiviManager,
            $this->userRepository,
            $this->parameterBag,
            $this->urlGenerator,
            true
        );
    }

    public function testGenerateWithSignalementsAndArretes(): void
    {
        $signalement = $this->getSignalement();
        $signalement->setBanIdOccupant('13202_0001');

        $signalementSameAddress = $this->getSignalement(nom: 'Nom Occupant');
        $signalementSameAddress->setBanIdOccupant('13202_0001')
            ->setReference('2024-01')
            ->setStatut(SignalementStatus::ACTIVE);

        $this->signalementRepository->expects($this->once())
            ->method('findOnSameAddress')
            ->willReturn([$signalementSameAddress]);

        $arrete = $this->createArrete(ArreteType::MISE_EN_SECURITE, new \DateTimeImmutable('2024-01-01'), new \DateTimeImmutable('2024-02-01'));
        $this->arreteFinder->expects($this->once())
            ->method('find')
            ->with($signalement)
            ->willReturn([$arrete]);

        $this->parameterBag->expects($this->once())
            ->method('get')
            ->with('user_system_email')
            ->willReturn('system@example.com');

        $user = new User();
        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'system@example.com'])
            ->willReturn($user);

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with('back_addresses_history_index')
            ->willReturn('http://localhost/historique');

        $this->suiviManager->expects($this->once())
            ->method('createSuivi')
            ->with(
                $this->equalTo($signalement),
                $this->callback(static fn (mixed $description): bool => \is_string($description) && str_contains($description, '2024-01')
                        && str_contains($description, 'Le dossier')
                        && str_contains($description, 'Un arrêté de type')
                        && str_contains($description, 'http://localhost/historique')),
                $this->equalTo(SuiviCategory::SIGNALEMENT_HISTORIQUE_EVENEMENT),
                $this->isNull(),
                $this->equalTo($user)
            );

        $this->historiqueEvenementsGenerator->generate($signalement);
    }

    public function testGenerateWithOnlySignalements(): void
    {
        $signalement = $this->getSignalement();
        $signalement->setBanIdOccupant('13202_0001');

        $signalementSameAddress = $this->getSignalement(nom: 'Nom Occupant');
        $signalementSameAddress->setBanIdOccupant('13202_0001')
            ->setReference('2024-01')
            ->setStatut(SignalementStatus::ACTIVE);

        $this->signalementRepository->expects($this->once())
            ->method('findOnSameAddress')
            ->willReturn([$signalementSameAddress]);

        $this->arreteFinder->expects($this->once())
            ->method('find')
            ->willReturn([]);

        $this->parameterBag->expects($this->once())->method('get')->willReturn('system@example.com');
        $this->userRepository->expects($this->once())->method('findOneBy')->willReturn(new User());

        $this->urlGenerator->expects($this->once())->method('generate')->willReturn('http://localhost/historique');

        $this->suiviManager->expects($this->once())
            ->method('createSuivi')
            ->with(
                $this->equalTo($signalement),
                $this->callback(static fn (mixed $description): bool => \is_string($description)
                    && str_contains($description, '2024-01')
                    && str_contains($description, 'http://localhost/historique')
                    && !str_contains($description, 'arrêté')),
                $this->equalTo(SuiviCategory::SIGNALEMENT_HISTORIQUE_EVENEMENT)
            );

        $this->historiqueEvenementsGenerator->generate($signalement);
    }

    public function testGenerateWithOnlyArretes(): void
    {
        $signalement = $this->getSignalement();
        $signalement->setBanIdOccupant('13202_0001');

        $this->signalementRepository->expects($this->once())
            ->method('findOnSameAddress')
            ->willReturn([]);

        $arreteWithoutMainLevee = $this->createArrete(ArreteType::MISE_EN_SECURITE, new \DateTimeImmutable('2024-01-01'), null);
        $arreteWithMainLevee = $this->createArrete(ArreteType::ARRETE_L_1331_26, new \DateTimeImmutable('2024-02-01'), new \DateTimeImmutable('2024-03-01'));

        $this->arreteFinder->expects($this->once())
            ->method('find')
            ->willReturn([$arreteWithoutMainLevee, $arreteWithMainLevee]);

        $this->parameterBag->expects($this->once())->method('get')->willReturn('system@example.com');
        $this->userRepository->expects($this->once())->method('findOneBy')->willReturn(new User());

        $this->urlGenerator->expects($this->once())->method('generate')->willReturn('http://localhost/historique');

        $this->suiviManager->expects($this->once())
            ->method('createSuivi')
            ->with(
                $this->equalTo($signalement),
                $this->callback(static fn (mixed $description): bool => \is_string($description)
                    && !str_contains($description, 'dossier')
                    && str_contains($description, 'sans main levée renseignée')
                    && str_contains($description, 'avec main levée le 01/03/2024')
                    && str_contains($description, 'Mise en sécurité')
                    && str_contains($description, 'Arrêté L1331-26')
                    && str_contains($description, 'http://localhost/historique')
                ),
                $this->equalTo(SuiviCategory::SIGNALEMENT_HISTORIQUE_EVENEMENT)
            );

        $this->historiqueEvenementsGenerator->generate($signalement);
    }

    public function testGenerateWithNoSignalementAndNoArrete(): void
    {
        $signalement = $this->getSignalement();
        $signalement->setBanIdOccupant('13202_0001');

        $this->signalementRepository->expects($this->once())
            ->method('findOnSameAddress')
            ->willReturn([]);

        $this->arreteFinder->expects($this->once())
            ->method('find')
            ->willReturn([]);

        $this->userRepository->expects($this->never())->method('findOneBy');
        $this->parameterBag->expects($this->never())->method('get');
        $this->suiviManager->expects($this->never())->method('createSuivi');

        $this->historiqueEvenementsGenerator->generate($signalement);
    }
}
