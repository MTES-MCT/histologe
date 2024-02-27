<?php

namespace App\Controller;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Enum\SignalementDraftStatus;
use App\Entity\SignalementDraft;
use App\Factory\SignalementDraftFactory;
use App\Manager\SignalementDraftManager;
use App\Serializer\SignalementDraftRequestSerializer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FrontNewSignalementController extends AbstractController
{
    #[Route('/nouveau-formulaire/signalement', name: 'front_nouveau_formulaire')]
    public function index(ParameterBagInterface $parameterBag): Response
    {
        return $this->redirectToRoute('front_signalement');
    }

    #[Route('/signalement-draft/{uuid}', name: 'front_nouveau_formulaire_edit', methods: 'GET')]
    public function edit(
        SignalementDraft $signalementDraft,
        ParameterBagInterface $parameterBag
    ): Response {
        if (!$parameterBag->get('feature_new_form')) {
            return $this->redirectToRoute('front_signalement');
        }

        return $this->render('front/nouveau_formulaire.html.twig', [
            'uuid_signalement' => $signalementDraft->getUuid(),
        ]);
    }

    #[Route('/signalement-draft/envoi', name: 'envoi_nouveau_signalement_draft', methods: 'POST')]
    // TODO : encore nécessaire ????
    public function sendSignalementDraft(
        Request $request,
        SignalementDraftRequestSerializer $serializer,
        SignalementDraftManager $signalementDraftManager,
        ValidatorInterface $validator,
    ): Response {
        /** @var SignalementDraftRequest $signalementDraftRequest */
        $signalementDraftRequest = $serializer->deserialize(
            $payload = $request->getContent(),
            SignalementDraftRequest::class,
            'json'
        );
        $errors = $validator->validate(
            $signalementDraftRequest,
            null,
            ['Default', 'POST_'.strtoupper($signalementDraftRequest->getProfil())]
        );
        if (0 === $errors->count()) {
            return $this->json([
                'uuid' => $signalementDraftManager->create(
                    $signalementDraftRequest,
                    json_decode($payload, true)
                ),
            ]);
        }

        return $this->json($errors);
    }

    #[Route('/signalement-draft/check', name: 'check_signalement_draft_existe', methods: 'POST')]
    public function checkSignalementDraftExists(
        Request $request,
        SignalementDraftRequestSerializer $serializer,
        SignalementDraftManager $signalementDraftManager,
        ValidatorInterface $validator,
        SignalementDraftFactory $signalementDraftFactory,
    ): Response {
        /** @var SignalementDraftRequest $signalementDraftRequest */
        $signalementDraftRequest = $serializer->deserialize(
            $payload = $request->getContent(),
            SignalementDraftRequest::class,
            'json'
        );
        $errors = $validator->validate(
            $signalementDraftRequest,
            null,
            ['Default', 'POST_'.strtoupper($signalementDraftRequest->getProfil())]
        );
        if (0 === $errors->count()) {
            $dataToHash = $signalementDraftFactory->getEmailDeclarant($signalementDraftRequest);
            $dataToHash .= $signalementDraftRequest->getAdresseLogementAdresse();
            $hash = hash('sha256', $dataToHash);

            // TODO : vérifier qu'il ne soit pas au statut ARCHIVE
            $existingSignalementDraft = $signalementDraftManager->findOneBy(['identificationHash' => $hash]);
            if (null !== $existingSignalementDraft) {
                return $this->json([
                    'already_exists' => true,
                    'uuid' => $existingSignalementDraft->getUuid(),
                    'created_at' => $existingSignalementDraft->getCreatedAt(),
                    'updated_at' => $existingSignalementDraft->getUpdatedAt(),
                ]);
            }

            return $this->json([
                'already_exists' => false,
                'uuid' => $signalementDraftManager->create(
                    $signalementDraftRequest,
                    json_decode($payload, true)
                ),
            ]);
        }

        return $this->json($errors);
    }

    #[Route('/signalement-draft/{uuid}/envoi', name: 'mise_a_jour_nouveau_signalement_draft', methods: 'PUT')]
    public function updateSignalementDraft(
        Request $request,
        SignalementDraftRequestSerializer $serializer,
        SignalementDraftManager $signalementDraftManager,
        ValidatorInterface $validator,
        SignalementDraft $signalementDraft,
    ): Response {
        /** @var SignalementDraftRequest $signalementDraftRequest */
        $signalementDraftRequest = $serializer->deserialize(
            $payload = $request->getContent(),
            SignalementDraftRequest::class,
            'json'
        );
        $groupValidation = ['Default', 'POST_'.strtoupper($signalementDraftRequest->getProfil())];
        if ('validation_signalement' === $signalementDraftRequest->getCurrentStep()) {
            $groupValidation[] = 'PUT_'.strtoupper($signalementDraftRequest->getProfil());
        }
        $errors = $validator->validate($signalementDraftRequest, null, $groupValidation);
        if (0 === $errors->count()) {
            $result = $signalementDraftManager->update(
                $signalementDraft,
                $signalementDraftRequest,
                json_decode($payload, true)
            );

            return $this->json($result);
        }

        return $this->json($errors);
    }

    #[Route('/signalement-draft/{uuid}/informations', name: 'informations_signalement_draft', methods: 'GET')]
    public function getSignalementDraft(
        SignalementDraft $signalementDraft,
    ): Response {
        return $this->json([
            'signalement' => SignalementDraftStatus::EN_COURS === $signalementDraft->getStatus()
                ? $signalementDraft :
                null,
        ]);
    }

    #[Route('/signalement-draft/{uuid}/send_mail', name: 'send_mail_continue_from_draft')]
    public function sendMailContinueFromDraft(
        NotificationMailerRegistry $notificationMailerRegistry,
        SignalementDraft $signalementDraft,
        Request $request
    ): Response {
        if ($request->isMethod('POST') && $signalementDraft) {
            $notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_CONTINUE_FROM_DRAFT,
                    to: $signalementDraft->getEmailDeclarant(),
                    signalementDraft: $signalementDraft,
                )
            );

            return $this->json(['success' => true]);
        }

        return $this->json(['response' => 'error'], Response::HTTP_BAD_REQUEST);
    }
}
