<?php

namespace App\Tests\Unit\Service\Signalement;

use App\Entity\Address;
use App\Entity\Enum\ProcedureCategory;
use App\Entity\Enum\ProcedureType;
use App\Entity\File;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\SignalementProcedure;
use App\Entity\Suivi;
use App\Entity\SuiviFile;
use App\Entity\Territory;
use App\Entity\User;
use App\Manager\SuiviManager;
use App\Service\Signalement\ProcedureEngageeService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormInterface;
use Twig\Environment;

class ProcedureEngageeServiceTest extends TestCase
{
    private Stub&EntityManagerInterface $entityManager;
    private MockObject&SuiviManager $suiviManager;
    private Stub&Security $security;
    private Stub&Environment $twig;
    private ProcedureEngageeService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->suiviManager = $this->createMock(SuiviManager::class);
        $this->security = $this->createStub(Security::class);
        $this->twig = $this->createStub(Environment::class);

        $this->service = new ProcedureEngageeService(
            $this->entityManager,
            $this->suiviManager,
            $this->security,
            $this->twig,
        );
    }

    public function testReturnsNullWhenProceduresCommentaireAndFilesAreUnchanged(): void
    {
        $file1 = $this->createFileStub(1, 'doc1.pdf');
        $file2 = $this->createFileStub(2, 'doc2.pdf');

        $deletedSuiviFile = (new SuiviFile())->setFile(null)->setTitle('deleted.pdf');
        $suiviFile1 = (new SuiviFile())->setFile($file1)->setTitle('doc1.pdf');
        $suiviFile2 = (new SuiviFile())->setFile($file2)->setTitle('doc2.pdf');

        $lastSuivi = new Suivi();
        $lastSuivi->setOriginalData(['commentaire' => '<p>Commentaire inchangé</p>']);
        $lastSuivi->addSuiviFile($suiviFile1);
        $lastSuivi->addSuiviFile($suiviFile2);
        $lastSuivi->addSuiviFile($deletedSuiviFile);

        $signalement = $this->createSignalementStub([ProcedureType::RSD], $lastSuivi);
        // Same files in reverse order
        $form = $this->createFormStub([ProcedureType::RSD], ' <p>Commentaire inchangé</p> ', [$file2, $file1]);

        $this->suiviManager->expects($this->never())->method('createSuivi');

        $isEdition = false;
        $result = $this->service->updateProcedureEngageeForSignalement($signalement, $form, $isEdition);

        $this->assertNull($result);
        $this->assertFalse($isEdition);
    }

    public function testCreatesSuiviWhenFileIsAddedWithSameProceduresAndCommentaire(): void
    {
        $file1 = $this->createFileStub(1, 'doc1.pdf');
        $file2 = $this->createFileStub(2, 'doc2.pdf');

        $lastSuivi = new Suivi();
        $lastSuivi->setOriginalData(['commentaire' => '<p>Commentaire inchangé</p>']);
        $lastSuivi->addSuiviFile((new SuiviFile())->setFile($file1)->setTitle('doc1.pdf'));

        $signalement = $this->createSignalementStub([ProcedureType::RSD], $lastSuivi);
        $form = $this->createFormStub([ProcedureType::RSD], '<p>Commentaire inchangé</p>', [$file1, $file2]);

        $newSuivi = $this->configureCreationMocks();

        $isEdition = false;
        $result = $this->service->updateProcedureEngageeForSignalement($signalement, $form, $isEdition);

        $this->assertSame($newSuivi, $result);
        $this->assertTrue($isEdition);
    }

    public function testCreatesSuiviWhenFileIsRemovedWithSameProceduresAndCommentaire(): void
    {
        $file1 = $this->createFileStub(1, 'doc1.pdf');

        $lastSuivi = new Suivi();
        $lastSuivi->setOriginalData(['commentaire' => '<p>Commentaire inchangé</p>']);
        $lastSuivi->addSuiviFile((new SuiviFile())->setFile($file1)->setTitle('doc1.pdf'));

        $signalement = $this->createSignalementStub([ProcedureType::RSD], $lastSuivi);
        $form = $this->createFormStub([ProcedureType::RSD], '<p>Commentaire inchangé</p>', []);

        $newSuivi = $this->configureCreationMocks();

        $isEdition = false;
        $result = $this->service->updateProcedureEngageeForSignalement($signalement, $form, $isEdition);

        $this->assertSame($newSuivi, $result);
        $this->assertTrue($isEdition);
    }

    private function createFileStub(int $id, string $title): File
    {
        $file = $this->createStub(File::class);
        $file->method('getId')->willReturn($id);
        $file->method('getTitle')->willReturn($title);

        return $file;
    }

    /**
     * @param ProcedureType[] $procedures
     */
    private function createSignalementStub(array $procedures, ?Suivi $lastSuivi): Signalement
    {
        $signalementProcedures = new ArrayCollection(array_map(
            static fn (ProcedureType $type): SignalementProcedure => (new SignalementProcedure())
                ->setProcedureType($type)
                ->setProcedureCategory(ProcedureCategory::PROCEDURE_ENGAGEE),
            $procedures,
        ));

        $territory = $this->createStub(Territory::class);
        $address = $this->createStub(Address::class);
        $address->method('getTerritory')->willReturn($territory);

        $signalement = $this->createStub(Signalement::class);
        $signalement->method('getSuivisWithCategory')
            ->willReturn(new ArrayCollection($lastSuivi ? [$lastSuivi] : []));
        $signalement->method('getSignalementProcedures')
            ->willReturn($signalementProcedures);
        $signalement->method('getAddress')->willReturn($address);

        return $signalement;
    }

    /**
     * @param ProcedureType[] $procedures
     * @param File[]          $files
     *
     * @return FormInterface<mixed>
     */
    private function createFormStub(array $procedures, ?string $commentaire, array $files): FormInterface
    {
        $commentaireField = $this->createStub(FormInterface::class);
        $commentaireField->method('getData')->willReturn($commentaire);

        $proceduresField = $this->createStub(FormInterface::class);
        $proceduresField->method('getData')->willReturn($procedures);

        $filesField = $this->createStub(FormInterface::class);
        $filesField->method('getData')->willReturn($files);

        $form = $this->createStub(FormInterface::class);
        $form->method('get')->willReturnMap([
            ['commentaire', $commentaireField],
            ['procedureEngagees', $proceduresField],
            ['files', $filesField],
        ]);

        return $form;
    }

    private function configureCreationMocks(): Suivi
    {
        $partner = $this->createStub(Partner::class);
        $user = $this->createStub(User::class);
        $user->method('getPartnerInTerritoryOrFirstOne')->willReturn($partner);
        $this->security->method('getUser')->willReturn($user);
        $this->twig->method('render')->willReturn('description');

        $newSuivi = new Suivi();
        $this->suiviManager->expects($this->once())->method('createSuivi')->willReturn($newSuivi);

        return $newSuivi;
    }
}
