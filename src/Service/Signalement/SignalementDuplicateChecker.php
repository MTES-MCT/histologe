<?php

namespace App\Service\Signalement;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Signalement;
use App\Manager\SignalementDraftManager;
use App\Repository\SignalementRepository;

class SignalementDuplicateChecker
{
    private const NB_DAYS_RECENTLY_CREATED_SIGNALEMENT = 90;

    public function __construct(
        private readonly SignalementDraftManager $signalementDraftManager,
        private readonly SignalementRepository $signalementRepository,
    ) {
    }

    /**
     * @return array<mixed>
     */
    public function check(SignalementDraftRequest $signalementDraftRequest): array
    {
        $isTiersDeclarant = SignalementDraftHelper::isTiersDeclarant($signalementDraftRequest);
        $existingSignalements = $this->signalementRepository->findAllForEmailAndAddress(
            SignalementDraftHelper::getEmailDeclarant($signalementDraftRequest),
            $signalementDraftRequest->getAdresseLogementAdresseDetailNumero(),
            $signalementDraftRequest->getAdresseLogementAdresseDetailCodePostal(),
            $signalementDraftRequest->getAdresseLogementAdresseDetailCommune(),
            $isTiersDeclarant
        );

        $existingSignalementDraft = $this->signalementDraftManager->findSignalementDraftByAddressAndMail(
            $signalementDraftRequest,
        );

        if (!empty($existingSignalements)) {
            $signalements = array_map(function (Signalement $existingSignalement) {
                $todayDate = new \DateTimeImmutable();
                $durationSinceCreated = $todayDate->diff($existingSignalement->getCreatedAt());

                return [
                    'uuid' => $existingSignalement->getUuid(),
                    'created_at' => $existingSignalement->getCreatedAt(),
                    'has_created_recently' => ($durationSinceCreated->days <= self::NB_DAYS_RECENTLY_CREATED_SIGNALEMENT),
                    'prenom_occupant' => $existingSignalement->getPrenomOccupant(),
                    'nom_occupant' => $existingSignalement->getNomOccupant(),
                    'complement_adresse_occupant' => $existingSignalement->getComplementAdresseOccupant(),
                ];
            }, $existingSignalements);

            $hasCreatedRecently = false;
            foreach ($signalements as $signalement) {
                $hasCreatedRecently = $hasCreatedRecently || $signalement['has_created_recently'];
            }

            return [
                'already_exists' => true,
                'type' => 'signalement',
                'signalements' => $signalements,
                'has_created_recently' => $hasCreatedRecently,
                'draft_exists' => (bool) $existingSignalementDraft,
            ];
        }

        if (null !== $existingSignalementDraft) {
            return [
                'already_exists' => true,
                'type' => 'draft',
                'draft_exists' => true,
                'created_at' => $existingSignalementDraft->getCreatedAt(),
                'updated_at' => $existingSignalementDraft->getUpdatedAt(),
            ];
        }

        return [
            'already_exists' => false,
            'uuid' => 'waiting_creation',
        ];
    }
}
