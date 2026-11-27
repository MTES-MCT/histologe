<?php

namespace App\Tests\Functional\Command;

use App\Entity\Signalement;
use App\Entity\User;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class FixSignalementUsagerCommandTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private SignalementRepository $signalementRepository;
    private UserRepository $userRepository;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);

        $application = new Application($kernel);
        $this->commandTester = new CommandTester($application->find('app:fix-signalement-usager'));
    }

    public function testSignalementNotFound(): void
    {
        $this->commandTester->execute(['uuid' => 'not-an-uuid']);

        $this->assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Aucun signalement trouvé', $this->commandTester->getDisplay());
    }

    public function testNothingToFix(): void
    {
        $this->commandTester->execute(['uuid' => '00000000-0000-0000-2022-000000000003']);

        $this->commandTester->assertCommandIsSuccessful();
        $display = $this->getDisplay();
        $this->assertStringContainsString('correspond aux données du signalement', $display);
        $this->assertStringContainsString('pas d\'e-mail déclarant dans le signalement', $display);
        $this->assertStringContainsString('Aucune modification effectuée.', $display);
    }

    public function testQ1ReplaceUserInformation(): void
    {
        $signalement = $this->changeOccupant('00000000-0000-0000-2022-000000000005', 'nouvel.occupant@example.com', 'Jeanne', 'Martin');
        $userId = $signalement->getSignalementUsager()->getOccupant()->getId();

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute(['uuid' => $signalement->getUuid()]);

        $this->commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('1 modification(s) enregistrée(s)', $this->getDisplay());

        $this->entityManager->clear();
        $user = $this->userRepository->find($userId);
        $this->assertSame('nouvel.occupant@example.com', $user->getEmail());
        $this->assertSame('Jeanne', $user->getPrenom());
        $this->assertSame('Martin', $user->getNom());
    }

    public function testQ1DryRunDoesNotPersist(): void
    {
        $signalement = $this->changeOccupant('00000000-0000-0000-2022-000000000005', 'nouvel.occupant@example.com', 'Jeanne', 'Martin');
        $user = $signalement->getSignalementUsager()->getOccupant();
        $userId = $user->getId();
        $initialEmail = $user->getEmail();

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute(['uuid' => $signalement->getUuid(), '--dry-run' => true]);

        $this->commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('ces modifications ont été annulées', $this->getDisplay());

        $this->entityManager->clear();
        $this->assertSame($initialEmail, $this->userRepository->find($userId)->getEmail());
    }

    public function testQ1RefusedWhenOnlyNameDiffersStops(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2022-000000000005']);
        $signalement->setPrenomOccupant('Autre prénom');
        $this->entityManager->flush();
        $userId = $signalement->getSignalementUsager()->getOccupant()->getId();

        $this->commandTester->setInputs(['no']);
        $this->commandTester->execute(['uuid' => $signalement->getUuid()]);

        $this->commandTester->assertCommandIsSuccessful();
        $display = $this->getDisplay();
        $this->assertStringContainsString('le prénom ou le nom diffère', $display);
        $this->assertStringNotContainsString('V2', $display);

        $this->entityManager->clear();
        $this->assertNotSame('Autre prénom', $this->userRepository->find($userId)->getPrenom());
    }

    public function testQ3CreateNewUsager(): void
    {
        $signalement = $this->changeOccupant('00000000-0000-0000-2022-000000000006', 'nouvel.occupant@example.com', 'Jeanne', 'Martin');
        $previousUser = $signalement->getSignalementUsager()->getOccupant();
        $previousUserId = $previousUser->getId();
        $previousEmail = $previousUser->getEmail();

        $this->commandTester->setInputs(['no', 'yes']);
        $this->commandTester->execute(['uuid' => $signalement->getUuid()]);

        $this->commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('n\'est plus lié à aucun signalement', $this->getDisplay());

        $this->entityManager->clear();
        $newUser = $this->userRepository->findOneBy(['email' => 'nouvel.occupant@example.com']);
        $this->assertNotNull($newUser);
        $this->assertTrue($newUser->isUsager());
        $this->assertSame('Jeanne', $newUser->getPrenom());
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2022-000000000006']);
        $this->assertSame($newUser->getId(), $signalement->getSignalementUsager()->getOccupant()->getId());
        $this->assertSame($previousEmail, $this->userRepository->find($previousUserId)->getEmail());
    }

    public function testQ3Refused(): void
    {
        $signalement = $this->changeOccupant('00000000-0000-0000-2022-000000000006', 'nouvel.occupant@example.com', 'Jeanne', 'Martin');
        $previousUserId = $signalement->getSignalementUsager()->getOccupant()->getId();

        $this->commandTester->setInputs(['no', 'no']);
        $this->commandTester->execute(['uuid' => $signalement->getUuid()]);

        $this->commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Création refusée', $this->getDisplay());

        $this->entityManager->clear();
        $this->assertNull($this->userRepository->findOneBy(['email' => 'nouvel.occupant@example.com']));
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2022-000000000006']);
        $this->assertSame($previousUserId, $signalement->getSignalementUsager()->getOccupant()->getId());
    }

    public function testQ1BlockedByExistingEmailThenQ2LinkExistingUser(): void
    {
        $otherUser = $this->getOccupant('00000000-0000-0000-2022-000000000008');
        $signalement = $this->changeOccupant(
            '00000000-0000-0000-2022-000000000007',
            $otherUser->getEmail(),
            $otherUser->getPrenom(),
            $otherUser->getNom()
        );

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute(['uuid' => $signalement->getUuid()]);

        $this->commandTester->assertCommandIsSuccessful();
        $display = $this->getDisplay();
        $this->assertStringContainsString('Q1 impossible', $display);
        $this->assertStringContainsString(sprintf('déjà utilisé par le user #%d', $otherUser->getId()), $display);
        $this->assertStringContainsString(sprintf('lier le user #%d', $otherUser->getId()), $display);

        $this->entityManager->clear();
        $this->assertSame($otherUser->getId(), $this->getOccupant('00000000-0000-0000-2022-000000000007')->getId());
    }

    public function testQ2Refused(): void
    {
        $otherUser = $this->getOccupant('00000000-0000-0000-2022-000000000008');
        $signalement = $this->changeOccupant(
            '00000000-0000-0000-2022-000000000007',
            $otherUser->getEmail(),
            $otherUser->getPrenom(),
            $otherUser->getNom()
        );
        $previousUserId = $signalement->getSignalementUsager()->getOccupant()->getId();

        $this->commandTester->setInputs(['no']);
        $this->commandTester->execute(['uuid' => $signalement->getUuid()]);

        $this->commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Liaison refusée', $this->getDisplay());

        $this->entityManager->clear();
        $this->assertSame($previousUserId, $this->getOccupant('00000000-0000-0000-2022-000000000007')->getId());
    }

    public function testQ1BlockedForPartnerUser(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2022-000000000001']);
        $partnerUser = $signalement->getSignalementUsager()->getDeclarant();
        $this->assertFalse($partnerUser->isUsager());
        $partnerEmail = $partnerUser->getEmail();
        $signalement->setMailDeclarant('nouveau.declarant@example.com');
        $this->entityManager->flush();

        $this->commandTester->setInputs(['no']);
        $this->commandTester->execute(['uuid' => $signalement->getUuid()]);

        $this->commandTester->assertCommandIsSuccessful();
        $display = $this->getDisplay();
        $this->assertStringContainsString('e-mail technique : cohérent', $display);
        $this->assertStringContainsString('Q1 impossible', $display);
        $this->assertStringContainsString('n\'est pas un usager', $display);
        $this->assertStringContainsString('Q3', $display);

        $this->entityManager->clear();
        $this->assertSame($partnerEmail, $this->userRepository->find($partnerUser->getId())->getEmail());
    }

    private function changeOccupant(string $uuid, string $mail, ?string $prenom, ?string $nom): Signalement
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => $uuid]);
        $signalement->setMailOccupant($mail)->setPrenomOccupant($prenom)->setNomOccupant($nom);
        $this->entityManager->flush();

        return $signalement;
    }

    private function getOccupant(string $uuid): User
    {
        return $this->signalementRepository->findOneBy(['uuid' => $uuid])->getSignalementUsager()->getOccupant();
    }

    /**
     * Supprime les retours à la ligne de SymfonyStyle pour faciliter les assertions.
     */
    private function getDisplay(): string
    {
        $display = preg_replace('/\s*\n\s*(?:!\s+|\[\w+\]\s+)?/', ' ', $this->commandTester->getDisplay());

        return preg_replace('/\s+/', ' ', (string) $display);
    }
}
