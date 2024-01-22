<?php

namespace App\Manager;

use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Factory\SignalementQualificationFactory;
use Doctrine\Persistence\ManagerRegistry;

class SignalementQualificationManager extends AbstractManager
{
    public function __construct(
        private readonly SignalementQualificationFactory $signalementQualificationFactory,
        protected ManagerRegistry $managerRegistry,
        string $entityName = SignalementQualification::class
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    /**
     * @param array $data The array representing the DesordrePrecision.
     *                    - 'listDesordrePrecisionsIds' (array): id of linked desordrePrecision
     *                    - 'listCriticiteIds' (array): id of linked criticites
     *                    - 'dernierBailAt' (DateTimeImmutable): date of dernier bail
     *                    - 'details' (array): json of details
     */
    public function createOrUpdate(
        Qualification $qualification,
        QualificationStatus $qualificationStatus,
        bool $isPostVisite,
        Signalement $signalement,
        array $data,
    ): SignalementQualification {
        /** @var SignalementQualification|null $signalementQualification */
        $signalementQualification = $this->getRepository()->findOneBy([
            'qualification' => $qualification,
            'isPostVisite' => $isPostVisite,
            'signalementId' => $signalement->getId(),
        ]);

        if (null === $signalementQualification) {
            $signalementQualification = $this->signalementQualificationFactory->createInstanceFrom(
                qualification: $qualification,
                qualificationStatus: $qualificationStatus,
                isPostVisite: $isPostVisite,
            );
        } else {
            $signalementQualification->setStatus($qualificationStatus);
        }

        if (isset($data['listDesordrePrecisionsIds'])) {
            $signalementQualification->setDesordrePrecisionIds($data['listDesordrePrecisionsIds']);
        }
        if (isset($data['listCriticiteIds'])) {
            $signalementQualification->setCriticites($data['listCriticiteIds']);
        }
        if (isset($data['dernierBailAt'])) {
            $signalementQualification->setDernierBailAt($data['dernierBailAt']);
        }
        if (isset($data['details'])) {
            $signalementQualification->setDetails($data['details']);
        }
        $signalementQualification->setSignalement($signalement);

        return $signalementQualification;
    }
}
