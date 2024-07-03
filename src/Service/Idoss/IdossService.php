<?php

namespace App\Service\Idoss;

use App\Entity\Affectation;
use App\Entity\JobEvent;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Manager\JobEventManager;
use App\Messenger\Message\Idoss\DossierMessage;
use App\Service\ImageManipulationHandler;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class IdossService
{
    public const TYPE_SERVICE = 'idoss';

    public const STATUS_ACCEPTED = 'accepte';
    public const STATUS_IN_PROGRESS = 'en_cours';
    public const STATUS_CLOSED = 'termine';
    public const MAPPING_STATUS = [
        self::STATUS_ACCEPTED => Affectation::STATUS_ACCEPTED,
        self::STATUS_IN_PROGRESS => Affectation::STATUS_ACCEPTED,
        self::STATUS_CLOSED => Affectation::STATUS_CLOSED,
    ];
    private const ACTION_PUSH_DOSSIER = 'push_dossier';
    private const ACTION_UPLOAD_FILES = 'upload_files';
    private const ACTION_LIST_STATUTS = 'list_statuts';
    private const AUTHENTICATE_ENDPOINT = '/api/Utilisateur/authentification';
    private const CREATE_DOSSIER_ENDPOINT = '/api/EtatCivil/creatDossHistologe';
    private const UPLOAD_FILES_ENDPOINT = '/api/EtatCivil/uploadFileRepoHistologe';
    private const LIST_STATUTS_ENDPOINT = '/api/EtatCivil/listStatutsHistologe';

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly ContainerBagInterface $params,
        private readonly EntityManagerInterface $entityManager,
        private readonly JobEventManager $jobEventManager,
        private readonly SerializerInterface $serializer,
        private readonly FilesystemOperator $fileStorage,
        private readonly ImageManipulationHandler $imageManipulationHandler,
    ) {
    }

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

    public function uploadFiles(Partner $partner, Signalement $signalement): JobEvent
    {
        $files = [];
        $filesJson = [];
        foreach ($signalement->getFiles() as $file) {
            if ($file->getSynchroData(self::TYPE_SERVICE)) {
                continue;
            }
            $files[] = $file;
            $filesJson[] = ['id' => $file->getId(), 'filename' => $file->getFilename()];
        }

        $url = $partner->getIdossUrl().self::UPLOAD_FILES_ENDPOINT;
        $payload = $this->getFilesPayload($signalement, $files);
        $jobAction = self::ACTION_UPLOAD_FILES;
        $jobMessage = json_encode($filesJson, true);
        $signalementId = $signalement->getId();

        $jobEvent = $this->processRequestAndSaveJobEvent($partner, $url, $jobAction, $jobMessage, $signalementId, $payload, 'POST', 'multipart/form-data');

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
            $statusCode = $response->getStatusCode();
            $status = Response::HTTP_OK === $statusCode ? JobEvent::STATUS_SUCCESS : JobEvent::STATUS_FAILED;
            $responseContent = $response->getContent(throw: false);
        } catch (\Exception $e) {
            $responseContent = $e->getMessage();
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

    private function getDossierPayload(DossierMessage $dossierMessage): array
    {
        $payload = [
            'user' => $this->params->get('idoss_username'),
            'Dossier' => [
                'UUIDSignalement' => $dossierMessage->getSignalementUuid(),
                'dateDepotSignalement' => $dossierMessage->getDateDepotSignalement(),
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

    private function getFilesPayload(Signalement $signalement, array $files): array
    {
        $payload = [
            'id' => $signalement->getSynchroData(self::TYPE_SERVICE)['id'],
            'uuid' => $signalement->getUuid(),
        ];
        $dataparts = [];
        foreach ($files as $file) {
            // $payload['file'] = $this->imageManipulationHandler->getFileRessource($file); //fonctionne
            $dataparts[] = ['file' => DataPart::fromPath($this->imageManipulationHandler->getFileRessource($file))];
        }

        $payload = array_merge($payload, $dataparts);
        // seconde version de test : ne fonctionne pas
        /*$payload = [
            ['name' => 'id', 'contents' => $signalement->getSynchroData(self::TYPE_SERVICE)['id']],
            ['name' => 'uuid', 'contents' => $signalement->getUuid()]
        ];
        foreach ($files as $file) {
            $payload[] = ['name' => 'file', 'contents' => $this->imageManipulationHandler->getFileRessource($file)];
        }*/

        return $payload;
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

    private function request(string $url, array $payload, ?string $token = null, $requestMethod = 'POST', $contentType = 'application/json'): ResponseInterface
    {
        $options = ['headers' => []];
        if ($token) {
            $options['headers']['Authorization'] = 'Bearer '.$token;
        }
        if ('multipart/form-data' === $contentType) {
            $formData = new FormDataPart($payload);
            $options['body'] = $formData->bodyToIterable();
            $options['headers'] = $formData->getPreparedHeaders()->toArray();
            $options['headers'][] = 'Authorization: Bearer '.$token;
        } else {
            $options['headers']['Content-Type'] = $contentType;
            $options['body'] = json_encode($payload);
        }

        return $this->client->request($requestMethod, $url, $options);
    }

//    private function request(string $url, array $payload, ?string $token = null, $requestMethod = 'POST', $contentType = 'application/json'): ResponseInterface
//    {
//        $options = ['headers' => []];
//        if ($token) {
//            $options['headers']['Authorization'] = 'Bearer '.$token;
//        }
//        if ('multipart/form-data' === $contentType) {
//            $client = new CurlHttpClient();
//            $curlFile = [];
//            if (isset($payload['file'])) {
//                foreach ($payload['file'] as $file) {
//                    $curlFile[] = new \CurlFile($file);
//                }
//            }
//
//            $payload['file'] = $curlFile;
//            $options['body'] = $payload;
//
//            return $client->request($requestMethod, $url, $options);
//        } else {
//            $options['headers']['Content-Type'] = $contentType;
//            $options['body'] = json_encode($payload);
//        }
//
//        return $this->client->request($requestMethod, $url, $options);
//    }
}
