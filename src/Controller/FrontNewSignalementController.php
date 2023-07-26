<?php

namespace App\Controller;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
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

        return $this->render('front/nouveau_formulaire.html.twig');
    }

    #[Route('/signalement/envoi', name: 'envoi_nouveau_signalement', methods: 'POST')]
    public function send(Request $request, ValidatorInterface $validator): Response
    {
        $serializer = new Serializer(
            [new ObjectNormalizer(nameConverter: new CamelCaseToSnakeCaseNameConverter())],
            [new JsonEncoder()]
        );
        $arrayPayload = $serializer->decode($payload = $request->getContent(), 'json');
        $signalementDraftRequest = $serializer->denormalize($arrayPayload, SignalementDraftRequest::class);

        /* @todo complete */
        $errors = $validator->validate($signalementDraftRequest);

        return $this->json('ok');
    }
}
