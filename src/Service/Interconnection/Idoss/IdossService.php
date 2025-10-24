<?php

namespace App\Service\Interconnection\Idoss;

use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\InterfacageType;
use App\Entity\File;
use App\Entity\JobEvent;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Manager\JobEventManager;
use App\Messenger\Message\Idoss\DossierMessage;
use App\Service\ImageManipulationHandler;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class IdossService
{
    public const string TYPE_SERVICE = InterfacageType::IDOSS->value;

    public const string STATUS_ACCEPTED = 'accepte';
    public const string STATUS_IN_PROGRESS = 'en_cours';
    public const string STATUS_CLOSED = 'termine';
    public const array MAPPING_STATUS = [
        self::STATUS_ACCEPTED => AffectationStatus::ACCEPTED,
        self::STATUS_IN_PROGRESS => AffectationStatus::ACCEPTED,
        self::STATUS_CLOSED => AffectationStatus::CLOSED,
    ];
    public const string ACTION_PUSH_DOSSIER = 'push_dossier';
    public const string ACTION_UPLOAD_FILES = 'upload_files';
    public const string ACTION_LIST_STATUTS = 'list_statuts';
    private const string AUTHENTICATE_ENDPOINT = '/api/Utilisateur/authentification';
    private const string CREATE_DOSSIER_ENDPOINT = '/api/EtatCivil/creatDossHistologe';
    private const string UPLOAD_FILES_ENDPOINT = '/api/EtatCivil/uploadFileRepoHistologe';
    private const string LIST_STATUTS_ENDPOINT = '/api/EtatCivil/listStatutsHistologe';
    private const int NB_MAX_FILES = 20;
    private const int CUSTOM_CODE_HTTP_BAD_REQUEST = 209;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly ContainerBagInterface $params,
        private readonly EntityManagerInterface $entityManager,
        private readonly JobEventManager $jobEventManager,
        private readonly SerializerInterface $serializer,
        private readonly ImageManipulationHandler $imageManipulationHandler,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function pushDossier(DossierMessage $dossierMessage): JobEvent
    {
        $partner = $this->entityManager->getRepository(Partner::class)->find($dossierMessage->getPartnerId());
        $url = $partner->getIdossUrl().self::CREATE_DOSSIER_ENDPOINT;
        $payload = $this->getDossierPayload($dossierMessage);
        $jobAction = self::ACTION_PUSH_DOSSIER;
        $jobMessage = $this->serializer->serialize($dossierMessage, 'json');
        $signalementId = $dossierMessage->getSignalementId();

        $jobEvent = $this->processRequestAndSaveJobEvent($partner, $url, $jobAction, $jobMessage, $signalementId, $payload);

        if (JobEvent::STATUS_SUCCESS === $jobEvent->getStatus()) {
            $signalement = $this->entityManager->getRepository(Signalement::class)->find($dossierMessage->getSignalementId());
            $jsonResponse = json_decode($jobEvent->getResponse(), true);
            $idossData = [
                'id' => $jsonResponse['id'],
                'created_at' => $jobEvent->getCreatedAt()->format('Y-m-d H:i:s'),
                'created_job_event_id' => $jobEvent->getId(),
            ];
            $signalement->setSynchroData($idossData, self::TYPE_SERVICE);
            $this->entityManager->flush();
        }

        return $jobEvent;
    }

    /**
     * @throws FilesystemException
     */
    public function uploadFiles(Partner $partner, Signalement $signalement): JobEvent|false
    {
        $files = [];
        $filesJson = [];
        foreach ($signalement->getFiles() as $file) {
            if ($file->getSynchroData(self::TYPE_SERVICE)) {
                continue;
            }
            $files[] = $file;
            $filesJson[] = ['id' => $file->getId(), 'filename' => $file->getFilename()];
            if (count($files) >= self::NB_MAX_FILES) {
                break;
            }
        }
        if (!\count($files)) {
            return false;
        }

        $url = $partner->getIdossUrl().self::UPLOAD_FILES_ENDPOINT;
        $payload = $this->getFilesPayload($signalement, $files);
        $jobAction = self::ACTION_UPLOAD_FILES;
        $jobMessage = (string) json_encode($payload, \JSON_HEX_TAG);
        $signalementId = $signalement->getId();

        $jobEvent = $this->processRequestAndSaveJobEvent(
            $partner,
            $url,
            $jobAction,
            $jobMessage,
            $signalementId,
            $payload,
            'POST',
            'multipart/form-data'
        );

        if (JobEvent::STATUS_SUCCESS === $jobEvent->getStatus()) {
            foreach ($files as $file) {
                $idossData = [
                    'uploaded_at' => $jobEvent->getCreatedAt()->format('Y-m-d H:i:s'),
                    'uploaded_job_event_id' => $jobEvent->getId(),
                ];
                $file->setSynchroData($idossData, self::TYPE_SERVICE);
                $this->entityManager->flush();
            }
        }

        return $jobEvent;
    }

    public function listStatuts(Partner $partner): JobEvent
    {
        $url = $partner->getIdossUrl().self::LIST_STATUTS_ENDPOINT;
        $jobAction = self::ACTION_LIST_STATUTS;

        return $this->processRequestAndSaveJobEvent(partner: $partner, url: $url, jobAction: $jobAction, requestMethod: 'GET');
    }

    /**
     * @param array<mixed> $payload
     */
    private function processRequestAndSaveJobEvent(
        Partner $partner,
        string $url,
        string $jobAction,
        string $jobMessage = '',
        ?int $signalementId = null,
        array $payload = [],
        string $requestMethod = 'POST',
        string $contentType = 'application/json',
    ): JobEvent {
        try {
            $token = $this->getToken($partner);
            $response = $this->request($url, $payload, $token, $requestMethod, $contentType);
            $statusCode = self::CUSTOM_CODE_HTTP_BAD_REQUEST === $response->getStatusCode()
                ? Response::HTTP_BAD_REQUEST
                : $response->getStatusCode();
            $status = Response::HTTP_OK === $statusCode ? JobEvent::STATUS_SUCCESS : JobEvent::STATUS_FAILED;
            $responseContent = $response->getContent(throw: false);
        } catch (\Throwable $e) {
            $responseContent = $e->getMessage();
            $this->logger->error('Idoss HTTP error occurred, cause : '.$e->getMessage());
            $status = JobEvent::STATUS_FAILED;
            $statusCode = 9999;
        }

        return $this->jobEventManager->createJobEvent(
            service: self::TYPE_SERVICE,
            action: $jobAction,
            message: $jobMessage,
            response: $responseContent,
            status: $status,
            codeStatus: $statusCode,
            signalementId: $signalementId,
            partnerId: $partner->getId(),
            partnerType: $partner->getType(),
        );
    }

    /**
     * @return array<mixed>
     */
    private function getDossierPayload(DossierMessage $dossierMessage): array
    {
        $payload = [
            'user' => $this->params->get('idoss_username'),
            'Dossier' => [
                'UUIDSignalement' => $dossierMessage->getSignalementUuid(),
                'dateDepotSignalement' => $dossierMessage->getDateDepotSignalement(),
                'referenceHistologe' => $dossierMessage->getReference(),
                'declarant' => $dossierMessage->getDeclarant(),
                'occupant' => $dossierMessage->getOccupant(),
                'proprietaire' => $dossierMessage->getProprietaire(),
                'bailEncours' => $dossierMessage->getBailEnCour(),
                'construitAv1949' => $dossierMessage->getConstruitAv1949(),
            ],
            'Etape' => $dossierMessage->getEtape(),
        ];
        if ($dossierMessage->getAdresse1()) {
            $payload['Dossier']['adresse1'] = $dossierMessage->getAdresse1();
        }
        if ($dossierMessage->getAdresse2()) {
            $payload['Dossier']['adresse2'] = $dossierMessage->getAdresse2();
        }
        if ($dossierMessage->getDescriptionProblemes()) {
            $payload['Dossier']['descriptionProblemes'] = $dossierMessage->getDescriptionProblemes();
        }
        if ($dossierMessage->getNumAllocataire()) {
            $payload['Dossier']['numAllocataire'] = $dossierMessage->getNumAllocataire();
        }
        if ($dossierMessage->getMontantAllocation()) {
            $payload['Dossier']['montantAllocation'] = $dossierMessage->getMontantAllocation();
        }
        if ($dossierMessage->getDateEntreeLogement()) {
            $payload['Dossier']['dateEntreeLogement'] = $dossierMessage->getDateEntreeLogement();
        }
        if ($dossierMessage->getMontantLoyer()) {
            $payload['Dossier']['montantLoyer'] = $dossierMessage->getMontantLoyer();
        }
        if ($dossierMessage->getNbrPieceLogement()) {
            $payload['Dossier']['nbrPieceLogement'] = $dossierMessage->getNbrPieceLogement();
        }
        if ($dossierMessage->getNbrEtages()) {
            $payload['Dossier']['nbrEtages'] = $dossierMessage->getNbrEtages();
        }

        return $payload;
    }

    /**
     * @param array<File> $files
     *
     * @return array<mixed>
     *
     * @throws FilesystemException
     */
    private function getFilesPayload(Signalement $signalement, array $files): array
    {
        $payload = [
            'id' => (string) $signalement->getSynchroData(self::TYPE_SERVICE)['id'],
            'uuid' => $signalement->getUuid(),
        ];
        $dataparts = [];
        foreach ($files as $file) {
            try {
                $filePath = $this->imageManipulationHandler->getFilePath($file);
                $dataparts[] = ['file' => DataPart::fromPath($filePath)];
            } catch (\Exception $e) {
                $this->logger->error('IdossService getFilesPayload on signalement uuid "'.$signalement->getUuid().'" and file id "'.$file->getId().'" throw : '.$e->getMessage());
            }
        }

        return array_merge($payload, $dataparts);
    }

    private function getToken(Partner $partner): string
    {
        if ($partner->getIdossToken() && $partner->getIdossTokenExpirationDate() && $partner->getIdossTokenExpirationDate() > new \DateTime()) {
            return $partner->getIdossToken();
        }

        $url = $partner->getIdossUrl().self::AUTHENTICATE_ENDPOINT;
        $payload = [
            'username' => $this->params->get('idoss_username'),
            'password' => $this->params->get('idoss_password'),
        ];

        $response = $this->request($url, $payload);
        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw new \Exception('Token not found : '.$response->getContent(throw: false));
        }
        $jsonResponse = json_decode($response->getContent());
        if (isset($jsonResponse->token) && isset($jsonResponse->expirationDate)) {
            $partner->setIdossToken($jsonResponse->token);
            $partner->setIdossTokenExpirationDate(new \DateTimeImmutable($jsonResponse->expirationDate));
            $this->entityManager->flush();

            return $jsonResponse->token;
        }
        throw new \Exception('Token not found : '.$response->getContent(throw: false));
    }

    /**
     * @param array<mixed> $payload
     */
    private function request(
        string $url,
        array $payload,
        ?string $token = null,
        string $requestMethod = 'POST',
        string $contentType = 'application/json',
    ): ResponseInterface {
        if (str_contains($this->params->get('host_url'), 'localhost')) {
            if (!str_contains($url, 'signal_logement_wiremock')) {
                throw new \LogicException('url must contain "signal_logement_wiremock" when on localhost.');
            }
        }
        $options = ['headers' => []];
        if ('multipart/form-data' === $contentType) {
            $formData = new FormDataPart($payload);
            $options['body'] = $formData->bodyToIterable();
            $options['headers'] = $formData->getPreparedHeaders()->toArray();
        } else {
            $options['headers']['Content-Type'] = $contentType;
            $options['body'] = json_encode($payload);
        }
        if ($token) {
            $options['headers']['Authorization'] = 'Bearer '.$token;
        }

        return $this->client->request($requestMethod, $url, $options);
    }
}
