<?php

namespace App\Controller\Api;

use App\Dto\Api\Model\Intervention as InterventionModel;
use App\Dto\Api\Request\FilesUploadRequest;
use App\Entity\Enum\DocumentType;
use App\Entity\File;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\User;
use App\Event\FileUploadedEvent;
use App\Event\InterventionEditedEvent;
use App\EventListener\SecurityApiExceptionListener;
use App\Factory\Api\InterventionFactory;
use App\Service\Signalement\SignalementFileProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[When('dev')]
#[When('test')]
#[Route('/api')]
class VisiteUploadDocumentsController extends AbstractController
{
    public const string TYPE_DOCUMENT_VISITE = 'rapport-visite';
    public const string TYPE_DOCUMENT_PHOTO = 'photos-visite';

    public function __construct(
        private readonly DenormalizerInterface $normalizer,
        private readonly ValidatorInterface $validator,
        private readonly SignalementFileProcessor $signalementFileProcessor,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly InterventionFactory $interventionFactory,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    #[OA\Post(
        path: '/api/interventions/{uuid}/{typeDocumentVisite}',
        description: 'Téléversement du rapport de visite ou des photos de visite.',
        summary: 'Téléversement du rapport de visite ou des photos de visite.',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            description: 'Données de téléversement du fichier',
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        'files' => new OA\Property(
                            property: 'files',
                            description: 'Liste des fichiers téléversés.',
                            type: 'array',
                            items: new OA\Items(type: 'string', format: 'binary'),
                        ),
                    ],
                    type: 'object',
                ),
                examples: [
                    new OA\Examples(
                        example: 'Exemple rapport de visite',
                        summary: "Envoi d'un rapport de visite.",
                        value: [
                            'files' => ['rapport_visite.pdf'],
                        ]
                    ),
                    new OA\Examples(
                        example: 'Exemple photos de visite',
                        summary: 'Envoi de plusieurs photos de visite.',
                        value: [
                            'files' => ['photo1.jpg', 'photo2.png', 'photo3.jpeg'],
                        ]
                    ),
                ]
            )
        ),

        tags: ['Interventions'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'Identifiant unique de l\'intervention.',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid'),
                example: '123e4567-e89b-12d3-a456-426655440000'
            ),
            new OA\Parameter(
                name: 'typeDocumentVisite',
                description: 'Type de document de visite.',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'string',
                    enum: [self::TYPE_DOCUMENT_VISITE, self::TYPE_DOCUMENT_PHOTO]
                ),
                example: 'rapport-visite'
            ),
        ],
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Une intervention.',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: InterventionModel::class))
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Erreur liée à la validation de la requête.',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Valeurs invalides pour les champs suivants :'),
                new OA\Property(property: 'status', type: 'integer', example: 400),
                new OA\Property(
                    property: 'errors',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'property', type: 'string', example: 'files'),
                            new OA\Property(property: 'message', type: 'string', example: 'Vous devez téléverser un fichier.'),
                            new OA\Property(property: 'invalidValue', type: 'array', items: new OA\Items(type: 'string'), example: []),
                        ]
                    )
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Accès non autorisé à la ressource.',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'integer', example: 401),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthorized'),
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: 'Un rapport de visite existe déjà pour cette intervention.',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'integer', example: 403),
                new OA\Property(property: 'message', type: 'string', example: 'Un rapport de visite existe déjà pour cette intervention.'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Signalement non trouvé.',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'integer', example: 404),
                new OA\Property(property: 'message', type: 'string', example: 'Resource Not Found'),
            ]
        )
    )]
    #[Route('/interventions/{uuid:intervention}/{typeDocumentVisite<rapport-visite|photos-visite>}',
        name: 'api_visites_documents_visite_post',
        methods: 'POST')]
    public function __invoke(
        Request $request,
        ?Intervention $intervention = null,
    ): JsonResponse {
        $typeDocumentVisite = $request->get('typeDocumentVisite');
        if (null === $intervention) {
            return $this->json([
                'message' => 'Intervention introuvable.',
                'status' => Response::HTTP_NOT_FOUND],
                Response::HTTP_NOT_FOUND
            );
        }
        $this->denyAccessUnlessGranted('INTERVENTION_EDIT_VISITE', $intervention, SecurityApiExceptionListener::ACCESS_DENIED);

        if (self::TYPE_DOCUMENT_VISITE === $typeDocumentVisite && !$this->canAddRapportVisite($intervention)) {
            return $this->json([
                'message' => 'Un rapport de visite existe déjà pour cette intervention.',
                'status' => Response::HTTP_FORBIDDEN],
                Response::HTTP_FORBIDDEN
            );
        }

        $signalement = $intervention->getSignalement();
        /** @var User $user */
        $user = $this->getUser();
        $data['files'] = $request->files->all();
        $filesUploadRequest = $this->normalizer->denormalize($data['files'], FilesUploadRequest::class, 'json');
        $groups = self::TYPE_DOCUMENT_VISITE === $typeDocumentVisite ? ['single'] : ['multiple_images'];
        $errors = $this->validator->validate(value: $filesUploadRequest, groups: $groups);
        if (count($errors) > 0) {
            throw new ValidationFailedException($filesUploadRequest, $errors);
        }

        $fileList = $this->uploadDocumentsVisite(
            $filesUploadRequest,
            $signalement,
            $intervention,
            $user,
            $this->getDocumentType($typeDocumentVisite),
            $this->getCategoryUploadType($typeDocumentVisite),
        );

        $this->entityManager->persist($signalement);
        $this->entityManager->flush();

        if ($this->isRapportVisite($typeDocumentVisite)) {
            $this->eventDispatcher->dispatch(
                new InterventionEditedEvent($intervention, $user, true),
                InterventionEditedEvent::NAME
            );
        } else {
            $this->eventDispatcher->dispatch(
                new FileUploadedEvent($signalement, $user, $fileList),
                FileUploadedEvent::NAME
            );
        }

        return $this->json($this->interventionFactory->createInstance($intervention), Response::HTTP_OK);
    }

    private function uploadDocumentsVisite(
        FilesUploadRequest $filesUploadRequest,
        Signalement $signalement,
        Intervention $intervention,
        User $user,
        DocumentType $documentType,
        string $categoryUploadType,
    ): array {
        $files = [];
        foreach ($filesUploadRequest->files as $file) {
            /* @var UploadedFile $file */
            $files[$categoryUploadType][] = $file;
        }
        $processedFiles = $this->signalementFileProcessor->process(
            $files,
            $categoryUploadType,
            $documentType,
        );
        $this->signalementFileProcessor->addFilesToSignalement(
            fileList: $processedFiles,
            signalement: $signalement,
            user: $user,
            intervention: $intervention,
        );

        return $processedFiles;
    }

    private function getDocumentType(string $typeDocumentVisite): DocumentType
    {
        return self::TYPE_DOCUMENT_VISITE === $typeDocumentVisite
            ? DocumentType::PROCEDURE_RAPPORT_DE_VISITE
            : DocumentType::PHOTO_VISITE;
    }

    private function getCategoryUploadType(string $typeDocumentVisite): string
    {
        return self::TYPE_DOCUMENT_VISITE === $typeDocumentVisite
            ? File::INPUT_NAME_DOCUMENTS
            : File::INPUT_NAME_PHOTOS;
    }

    private function isRapportVisite(string $typeDocumentVisite): bool
    {
        return self::TYPE_DOCUMENT_VISITE === $typeDocumentVisite;
    }

    public function canAddRapportVisite(Intervention $intervention): bool
    {
        return $intervention->getRapportDeVisite()->isEmpty();
    }
}
