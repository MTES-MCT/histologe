<?php

namespace App\Controller;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\SignalementDraft;
use App\Manager\SignalementDraftManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
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

        return $this->render('front/nouveau_formulaire.html.twig', [
            'uuid_signalement' => null,
        ]);
    }

    #[Route('/signalement/{uuid}', name: 'front_nouveau_formulaire_edit')]
    public function edit(
        SignalementDraft $signalementDraft,
        ParameterBagInterface $parameterBag
    ): Response {
        // TODO : sécurité ?

        if (!$parameterBag->get('feature_new_form')) {
            return $this->redirectToRoute('front_signalement');
        }

        return $this->render('front/nouveau_formulaire.html.twig', [
            'uuid_signalement' => $signalementDraft->getUuid(),
        ]);
    }

    #[Route('/signalement-draft/envoi', name: 'envoi_nouveau_signalement_draft', methods: 'POST')]
    public function sendSignalementDraft(
        Request $request,
        SerializerInterface $serializer,
        SignalementDraftManager $signalementDraftManager,
        ValidatorInterface $validator,
    ): Response {
        /** @var SignalementDraftRequest $signalementDraftRequest */
        $signalementDraftRequest = $serializer->deserialize(
            $payload = $request->getContent(),
            SignalementDraftRequest::class,
            'json'
        );
        $errors = $validator->validate($signalementDraftRequest);
        if (0 === $errors->count()) {
            return $this->json([
                'uuid' => $signalementDraftManager->create(
                    $signalementDraftRequest,
                    json_decode($payload, true)
                ),
            ]);
        }

        return $this->json('@todo error');
    }

    #[Route('/signalement-draft/{uuid}/envoi', name: 'mise_a_jour_nouveau_signalement_draft', methods: 'PUT')]
    public function updateSignalementDraft(
        Request $request,
        SerializerInterface $serializer,
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

        $errors = $validator->validate($signalementDraftRequest);
        if (0 === $errors->count()) {
            return $this->json([
                'uuid' => $signalementDraftManager->update(
                    $signalementDraft,
                    $signalementDraftRequest,
                    json_decode($payload, true)
                ),
            ]);
        }

        return $this->json('@todo error');
    }

    #[Route('/signalement-draft/{uuid}/informations', name: 'informations_signalement_draft', methods: 'GET')]
    public function getSignalementDraft(
        SignalementDraft $signalementDraft,
    ): Response {
        return $this->json([
            'signalement' => $signalementDraft,
        ]);
    }
}
