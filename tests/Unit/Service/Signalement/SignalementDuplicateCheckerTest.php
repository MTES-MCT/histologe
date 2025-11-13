<?php

namespace App\Tests\Unit\Service\Signalement;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Signalement;
use App\Entity\SignalementDraft;
use App\Repository\SignalementDraftRepository;
use App\Repository\SignalementRepository;
use App\Serializer\SignalementDraftRequestSerializer;
use App\Service\Signalement\SignalementDuplicateChecker;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SignalementDuplicateCheckerTest extends KernelTestCase
{
    public function testCheckSignalementOld(): void
    {
        $uuid = '00000000-0000-0000-2022-000000000008';

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy([
            'uuid' => $uuid,
        ]);

        /** @var SignalementDraftRequestSerializer $serializer */
        $serializer = static::getContainer()->get(SignalementDraftRequestSerializer::class);
        $payloadLocataireSignalement = (string) file_get_contents(__DIR__.'../../../../files/post_signalement_draft_payload.json');
        /** @var SignalementDraftRequest $signalementDraftRequest */
        $signalementDraftRequest = $serializer->deserialize(
            $payloadLocataireSignalement,
            SignalementDraftRequest::class,
            'json'
        );

        $signalementDraftRequest->setProfil(ProfileDeclarant::LOCATAIRE->name);
        $signalementDraftRequest->setAdresseLogementAdresseDetailNumero($signalement->getAdresseOccupant());
        $signalementDraftRequest->setAdresseLogementAdresseDetailCodePostal($signalement->getCpOccupant());
        $signalementDraftRequest->setAdresseLogementAdresseDetailCommune($signalement->getVilleOccupant());
        $signalementDraftRequest->setVosCoordonneesOccupantEmail($signalement->getMailOccupant());

        /** @var SignalementDuplicateChecker $signalementDuplicateChecker */
        $signalementDuplicateChecker = static::getContainer()->get(SignalementDuplicateChecker::class);
        $result = $signalementDuplicateChecker->check($signalementDraftRequest);

        $this->assertEquals($result['already_exists'], true);
        $this->assertEquals($result['type'], 'signalement');
        $this->assertEquals($result['has_created_recently'], false);
        $this->assertEquals($result['signalements'][0]['uuid'], $uuid);
    }

    public function testCheckSignalementRecent(): void
    {
        $uuid = '00000000-0000-0000-2022-000000000001';

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy([
            'uuid' => $uuid,
        ]);

        /** @var SignalementDraftRequestSerializer $serializer */
        $serializer = static::getContainer()->get(SignalementDraftRequestSerializer::class);
        $payloadLocataireSignalement = (string) file_get_contents(__DIR__.'../../../../files/post_signalement_draft_payload.json');
        /** @var SignalementDraftRequest $signalementDraftRequest */
        $signalementDraftRequest = $serializer->deserialize(
            $payloadLocataireSignalement,
            SignalementDraftRequest::class,
            'json'
        );

        $signalementDraftRequest->setProfil(ProfileDeclarant::TIERS_PRO->name);
        $signalementDraftRequest->setAdresseLogementAdresseDetailNumero($signalement->getAdresseOccupant());
        $signalementDraftRequest->setAdresseLogementAdresseDetailCodePostal($signalement->getCpOccupant());
        $signalementDraftRequest->setAdresseLogementAdresseDetailCommune($signalement->getVilleOccupant());
        $signalementDraftRequest->setCoordonneesOccupantEmail($signalement->getMailOccupant());
        $signalementDraftRequest->setCoordonneesOccupantNom($signalement->getNomOccupant());
        $signalementDraftRequest->setVosCoordonneesTiersEmail($signalement->getMailDeclarant());

        /** @var SignalementDuplicateChecker $signalementDuplicateChecker */
        $signalementDuplicateChecker = static::getContainer()->get(SignalementDuplicateChecker::class);
        $result = $signalementDuplicateChecker->check($signalementDraftRequest);

        $this->assertEquals($result['already_exists'], true);
        $this->assertEquals($result['type'], 'signalement');
        $this->assertEquals($result['has_created_recently'], true);
        $this->assertEquals($result['signalements'][0]['uuid'], $uuid);
    }

    public function testCheckSignalementDraft(): void
    {
        $uuid = '00000000-0000-0000-2023-locataire001';

        /** @var SignalementDraftRepository $signalementDraftRepository */
        $signalementDraftRepository = static::getContainer()->get(SignalementDraftRepository::class);
        /** @var SignalementDraft $signalementDraft */
        $signalementDraft = $signalementDraftRepository->findOneBy([
            'uuid' => $uuid,
        ]);

        /** @var SignalementDraftRequestSerializer $serializer */
        $serializer = static::getContainer()->get(SignalementDraftRequestSerializer::class);
        $payloadLocataireSignalement = (string) file_get_contents(__DIR__.'../../../../files/post_signalement_draft_payload.json');
        /** @var SignalementDraftRequest $signalementDraftRequest */
        $signalementDraftRequest = $serializer->deserialize(
            $payloadLocataireSignalement,
            SignalementDraftRequest::class,
            'json'
        );

        $signalementDraftRequest->setAdresseLogementAdresse($signalementDraft->getAddressComplete());
        $signalementDraftRequest->setProfil(ProfileDeclarant::LOCATAIRE->name);
        $signalementDraftRequest->setAdresseLogementAdresseDetailNumero($signalementDraft->getPayload()['adresse_logement_adresse_detail_numero']);
        $signalementDraftRequest->setAdresseLogementAdresseDetailCodePostal($signalementDraft->getPayload()['adresse_logement_adresse_detail_code_postal']);
        $signalementDraftRequest->setAdresseLogementAdresseDetailCommune($signalementDraft->getPayload()['adresse_logement_adresse_detail_commune']);
        $signalementDraftRequest->setVosCoordonneesOccupantEmail($signalementDraft->getPayload()['vos_coordonnees_occupant_email']);

        /** @var SignalementDuplicateChecker $signalementDuplicateChecker */
        $signalementDuplicateChecker = static::getContainer()->get(SignalementDuplicateChecker::class);
        $result = $signalementDuplicateChecker->check($signalementDraftRequest);

        $this->assertEquals($result['already_exists'], true);
        $this->assertEquals($result['type'], 'draft');
    }
}
