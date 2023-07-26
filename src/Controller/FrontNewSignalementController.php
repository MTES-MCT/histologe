<?php

namespace App\Controller;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\SignalementDraft;
use App\Manager\SignalementDraftManager;
use App\Serializer\SignalementRequestDraftSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/nouveau-formulaire')]
class FrontNewSignalementController extends AbstractController
{
    #[Route('/signalement', name: 'front_nouveau_formulaire')]
    public function index(ParameterBagInterface $parameterBag): Response
    {
        if (!$parameterBag->get('feature_new_form')) {
            return $this->redirectToRoute('front_signalement');
        }

        return $this->render('front/nouveau_formulaire.html.twig');
    }

    #[Route('/signalement-draft/envoi', name: 'envoi_nouveau_signalement_draft', methods: 'POST')]
    public function sendSignalementDraft(
        Request $request,
        SignalementRequestDraftSerializer $serializer,
        SignalementDraftManager $signalementDraftManager,
        ValidatorInterface $validator,
    ): Response {
        $payload = $serializer->decode($request->getContent(), 'json');
        /** @var SignalementDraftRequest $signalementDraftRequest */
        $signalementDraftRequest = $serializer->denormalize($payload, SignalementDraftRequest::class);

        $errors = $validator->validate($signalementDraftRequest);
        if (0 === $errors->count()) {
            return $this->json([
                'uuid' => $signalementDraftManager->create($signalementDraftRequest, $payload),
            ]);
        }

        return $this->json('@todo error');
    }

    #[Route('/signalement-draft/{uuid}/envoi', name: 'mise_a_jour_nouveau_signalement_draft', methods: 'PUT')]
    public function updateSignalementDraft(
        Request $request,
        SignalementRequestDraftSerializer $serializer,
        SignalementDraftManager $signalementDraftManager,
        ValidatorInterface $validator,
        SignalementDraft $signalementDraft,
    ): Response {
        $payload = $serializer->decode($request->getContent(), 'json');
        /** @var SignalementDraftRequest $signalementDraftRequest */
        $signalementDraftRequest = $serializer->denormalize($payload, SignalementDraftRequest::class);

        $errors = $validator->validate($signalementDraftRequest);
        if (0 === $errors->count()) {
            return $this->json([
                'uuid' => $signalementDraftManager->update(
                    $signalementDraft,
                    $signalementDraftRequest,
                    $payload
                ),
            ]);
        }

        return $this->json('@todo error');
    }
}
