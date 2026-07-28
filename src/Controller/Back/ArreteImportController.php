<?php

declare(strict_types=1);

namespace App\Controller\Back;

use App\Entity\User;
use App\Form\ImportArreteType;
use App\Service\Import\Arrete\ArreteImportLoader;
use App\Service\Import\Arrete\ArreteImportRow;
use LongitudeOne\Spatial\Exception\InvalidValueException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/bo/gerer-territoire/arretes')]
class ArreteImportController extends AbstractController
{
    public function __construct(
        private readonly ArreteImportLoader $arreteImportLoader,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        #[Autowire(env: 'FEATURE_HISTO_ADDRESS')]
        private readonly bool $featureHistoAddress,
    ) {
        if (!$this->featureHistoAddress) {
            throw $this->createNotFoundException();
        }
    }

    #[Route('/import', name: 'back_arrete_import', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN_TERRITORY')]
    public function importCsv(): Response
    {
        $form = $this->createForm(ImportArreteType::class);

        return $this->render('back/arrete/import.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/import-upload', name: 'back_arrete_import_upload', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN_TERRITORY')]
    public function importUploadCsv(
        Request $request,
    ): Response {
        if (!$request->isXmlHttpRequest()) {
            return $this->json(['message' => 'Requête invalide'], Response::HTTP_BAD_REQUEST);
        }

        $form = $this->createForm(ImportArreteType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $csvFile = $form->get('file')->getData();
            /** @var User $user */
            $user = $this->getUser();
            [$errors, $data] = $this->arreteImportLoader->validate($csvFile->getPathname(), $user);
            if (!empty($errors)) {
                return $this->json(['errors' => $errors, 'data' => $data]);
            }

            return $this->json(['data' => $data]);
        }

        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return $this->json([
            'errors' => $errors ?: ['Formulaire non soumis.']],
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * @throws ExceptionInterface
     * @throws InvalidValueException
     */
    #[Route('/confirm', name: 'back_arrete_import_confirm', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN_TERRITORY')]
    public function importConfirm(
        Request $request,
    ): Response {
        if (!$request->isXmlHttpRequest()) {
            return $this->json(['message' => 'Requête invalide'], Response::HTTP_BAD_REQUEST);
        }

        /* @var ArreteImportRow[] $arreteImportRows */
        $arreteImportRows = $this->serializer->deserialize(
            $request->getContent(),
            ArreteImportRow::class.'[]',
            'json',
        );

        foreach ($arreteImportRows as $arreteImportRow) {
            $violations = $this->validator->validate($arreteImportRow);
            if ($violations->count() > 0) {
                return $this->json(['errors' => $violations], Response::HTTP_BAD_REQUEST);
            }
        }

        /** @var User $user */
        $user = $this->getUser();
        $this->arreteImportLoader->load($arreteImportRows, $user);

        $metadata = $this->arreteImportLoader->getMetadata();
        $errorsCount = isset($metadata['errors']) ? \count($metadata['errors']) : 0;
        if (0 === $errorsCount) {
            $successMessage = sprintf(
                '%s Les arrêtés sont désormais consultables sur votre territoire.',
                1 === (int) $metadata['countSuccess']
                    ? '1 arrêté a été importé.'
                    : sprintf('%d arrêtés ont été importés.', (int) $metadata['countSuccess'])
            );

            $this->addFlash('success', [
                'title' => 'Importation réussie',
                'message' => $successMessage,
            ]);
        } elseif ($errorsCount < \count($arreteImportRows)) {
            foreach ($metadata['errors'] as $errorMessage) {
                $this->addFlash('error', $errorMessage);
            }
            $successMessage = sprintf(
                '%s Les arrêtés non importés sont signalés ci-dessus. Les arrêtés importés sont désormais consultables sur votre territoire.',
                1 === (int) $metadata['countSuccess']
                    ? '1 arrêté a été importé.'
                    : sprintf('%d arrêtés ont été importés.', (int) $metadata['countSuccess'])
            );
            $this->addFlash('success', [
                'title' => 'Importation réussie',
                'message' => $successMessage,
            ]);
        } else {
            foreach ($metadata['errors'] as $errorMessage) {
                $this->addFlash('error', $errorMessage);
            }
        }

        return $this->json([]);
    }
}
