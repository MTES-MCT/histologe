<?php

namespace App\Controller\Api;

use App\Dto\Api\Model\File as FileResponse;
use App\Dto\Api\Request\FilesUploadRequest;
use App\Entity\Signalement;
use App\Entity\User;
use App\Event\FileUploadedEvent;
use App\EventListener\SecurityApiExceptionListener;
use App\Factory\Api\FileFactory;
use App\Security\Voter\Api\ApiSignalementPartnerVoter;
use App\Service\Security\PartnerAuthorizedResolver;
use App\Service\Signalement\SignalementFileProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class SignalementFileUploadController extends AbstractController
{
    public function __construct(
        private readonly DenormalizerInterface $normalizer,
        private readonly ValidatorInterface $validator,
        private readonly SignalementFileProcessor $signalementFileProcessor,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly FileFactory $fileFactory,
        private readonly LoggerInterface $logger,
        private readonly PartnerAuthorizedResolver $partnerAuthorizedResolver,
    ) {
    }

    /**
     * @throws ExceptionInterface
     * @throws \Throwable
     */
    #[Route('/signalements/{uuid:signalement}/files', name: 'api_signalements_files_post', methods: ['POST'])]
    #[OA\Post(
        path: '/api/signalements/{uuid}/files',
        description: 'Retourne les informations du fichier téléversé pour un signalement.',
        summary: 'Téléversement d\'un fichier',
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
                            description: 'Liste des fichiers téléversés',
                            type: 'array',
                            items: new OA\Items(type: 'string', format: 'binary'),
                        ),
                    ],
                    type: 'object'
                ),
                examples: [
                    new OA\Examples(
                        example: "Exemple d'envoi d'un fichier",
                        summary: "Exemple d'envoi d'un fichier",
                        value: [
                            'files' => ['file1.jpg', 'file2.pdf'],
                        ]
                    ),
                ]
            )
        ),
        tags: ['Fichiers'],
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Un document',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: FileResponse::class))
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
                            new OA\Property(property: 'message', type: 'string', example: 'Vous devez téléverser au moins un fichier.'),
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
        response: 404,
        description: 'Signalement non trouvé.',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'integer', example: 404),
                new OA\Property(property: 'message', type: 'string', example: 'Resource Not Found'),
            ]
        )
    )]
    public function __invoke(
        Request $request,
        ?Signalement $signalement = null,
    ): JsonResponse {
        if (null === $signalement) {
            return $this->json(
                ['message' => 'Signalement introuvable', 'status' => Response::HTTP_NOT_FOUND],
                Response::HTTP_NOT_FOUND
            );
        }
        /** @var User $user */
        $user = $this->getUser();
        $partner = $this->partnerAuthorizedResolver->getUniquePartner($user);
        if (!$partner) {
            throw new \Exception('TODO permission API : ajout du parametre partenaire facultatif. Si non fournit renvoyer une erreur demandant de l\'expliciter');
        }
        $this->denyAccessUnlessGranted(
            ApiSignalementPartnerVoter::API_EDIT_SIGNALEMENT,
            ['signalement' => $signalement, 'partner' => $partner],
            SecurityApiExceptionListener::ACCESS_DENIED
        );
        $data['files'] = $request->files->all();
        $filesUploadRequest = new FilesUploadRequest();
        try {
            $filesUploadRequest = $this->normalizer->denormalize($data['files'], FilesUploadRequest::class, 'json');
        } catch (NotNormalizableValueException $exception) {
            $this->logger->error($exception->getMessage());
        }

        $errors = $this->validator->validate(value: $filesUploadRequest, groups: ['multiple_documents']);
        if (count($errors) > 0) {
            throw new ValidationFailedException($filesUploadRequest, $errors);
        }

        $fileList = $this->signalementFileProcessor->process($filesUploadRequest->files);
        $this->signalementFileProcessor->addFilesToSignalement(
            fileList: $fileList,
            signalement: $signalement,
            user: $user
        );

        $this->entityManager->persist($signalement);
        $this->entityManager->flush();

        $fileUploadedEvent = $this->eventDispatcher->dispatch(
            new FileUploadedEvent($signalement, $user, $fileList),
            FileUploadedEvent::NAME
        );

        $response = $this->fileFactory->createFromArray($fileUploadedEvent->getFilesPushed());

        return $this->json($response, Response::HTTP_CREATED);
    }
}
