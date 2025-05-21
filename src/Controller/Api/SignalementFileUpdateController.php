<?php

namespace App\Controller\Api;

use App\Dto\Api\Request\FileRequest;
use App\Entity\Enum\DocumentType;
use App\Entity\File;
use App\Factory\Api\FileFactory;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class SignalementFileUpdateController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FileFactory $fileFactory,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/files/{uuid:file}', name: 'api_signalements_files_patch', methods: ['PATCH'])]
    #[OA\Patch(
        path: '/api/files/{uuid}',
        description: 'Edite le type de document ainsi que la description pour un fichier.',
        summary: 'Edition d\'un fichier',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            description: 'Mettre à jour le type de document et la description.',
            content: new OA\JsonContent(ref: '#/components/schemas/FileRequest')
        ),
        tags: ['Fichiers'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Fichier édité avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'uuid', type: 'string', example: '123'),
                        new OA\Property(property: 'documentType', type: 'string', example: 'BAILLEUR_REPONSE_BAILLEUR'),
                        new OA\Property(property: 'description', type: 'string', example: 'lorem ipsum dolor sit amet'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Erreur de validation ou autres erreurs liées à la requête.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'integer', example: 400),
                        new OA\Property(property: 'message', type: 'string', example: 'Validation Failed'),
                        new OA\Property(property: 'errors', type: 'array', items: new OA\Items(
                            type: 'object'
                        )),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Fichier non trouvé.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'integer', example: 404),
                        new OA\Property(property: 'message', type: 'string', example: 'Resource Not Found'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Accès non autorisé.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'integer', example: 401),
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthorized'),
                    ]
                )
            ),
        ]
    )]
    public function __invoke(
        #[MapRequestPayload(validationGroups: ['false'])]
        FileRequest $fileRequest,
        ?File $file = null,
    ): JsonResponse {
        if (null === $file) {
            return $this->json(
                ['message' => 'Fichier introuvable', 'status' => Response::HTTP_NOT_FOUND],
                Response::HTTP_NOT_FOUND
            );
        }
        $this->denyAccessUnlessGranted('FILE_EDIT', $file);

        $errors = $this->validator->validate($fileRequest);
        if (count($errors) > 0) {
            throw new ValidationFailedException($fileRequest, $errors);
        }

        $documentType = DocumentType::tryFrom($fileRequest->documentType);
        $ext = pathinfo($file->getFilename(), \PATHINFO_EXTENSION);
        if ((in_array($ext, File::IMAGE_EXTENSION) && 'pdf' !== $ext) && isset(DocumentType::getOrderedPhotosList()[$documentType->name])) {
            $file->setFileType(File::FILE_TYPE_PHOTO);
            $file->setDescription($fileRequest->description);
        } else {
            $file->setFileType(File::FILE_TYPE_DOCUMENT);
            $file->setDescription(null);
        }
        $file->setExtension($ext);
        $file->setDocumentType($documentType);
        $this->entityManager->flush();

        return $this->json($this->fileFactory->createFrom($file));
    }
}
