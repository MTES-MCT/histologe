<?php

namespace App\Tests\Functional\Specification\Signalement;

use App\Entity\Affectation;
use App\Entity\Enum\InterventionType;
use App\Entity\Intervention;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use App\Repository\UserRepository;
use App\Specification\Signalement\FirstAffectationAcceptedSpecification;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FirstAffectationAcceptedSpecificationTest extends KernelTestCase
{
    private SuiviRepository $suiviRepository;
    private SignalementRepository $signalementRepository;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->suiviRepository = static::getContainer()->get(SuiviRepository::class);
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);
    }

    public function testAddSuiviFirstAffectation(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2022-7']);
        $user = $this->userRepository->findOneBy(['email' => 'user-13-01@histologe.fr']);

        $affectation = (new Affectation())
            ->setPartner($user->getPartner())
            ->setTerritory($user->getTerritory())
            ->setAnsweredAt(new \DateTimeImmutable())
            ->setAnsweredBy($user)
            ->setStatut(Affectation::STATUS_ACCEPTED);

        $signalement->addAffectation($affectation);

        $firstAffectationAcceptedSpecification = new FirstAffectationAcceptedSpecification($this->suiviRepository);
        $canAddSuiviFirstAffectation = $firstAffectationAcceptedSpecification->isSatisfiedBy(
            $signalement,
            $affectation
        );

        $this->assertTrue($canAddSuiviFirstAffectation);
    }

    public function testDoNotAddSuiviFirstAffectation(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2023-20']);
        $user = $this->userRepository->findOneBy(['email' => 'user-13-01@histologe.fr']);

        $intervention = (new Intervention())
            ->setPartner($user->getPartner())
            ->setStatus(Intervention::STATUS_PLANNED)
            ->setType(InterventionType::VISITE)
            ->setScheduledAt((new \DateTimeImmutable())->modify('+1 month'));

        $affectation = (new Affectation())
            ->setPartner($user->getPartner())
            ->setTerritory($user->getTerritory())
            ->setAnsweredAt(new \DateTimeImmutable())
            ->setAnsweredBy($user)
            ->setStatut(Affectation::STATUS_ACCEPTED);

        $signalement
            ->addAffectation($affectation)
            ->addIntervention($intervention);

        $firstAffectationAcceptedSpecification = new FirstAffectationAcceptedSpecification($this->suiviRepository);
        $canAddSuiviFirstAffectation = $firstAffectationAcceptedSpecification->isSatisfiedBy(
            $signalement,
            $affectation
        );

        $this->assertFalse($canAddSuiviFirstAffectation);
    }
}
