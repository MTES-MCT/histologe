<?php

namespace App\Controller;

use App\Entity\Critere;
use App\Entity\Criticite;
use App\Entity\Enum\Qualification;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Entity\Situation;
use App\Entity\Suivi;
use App\Entity\User;
use App\Event\SignalementCreatedEvent;
use App\Exception\MaxUploadSizeExceededException;
use App\Form\SignalementType;
use App\Manager\UserManager;
use App\Repository\CritereRepository;
use App\Repository\SignalementRepository;
use App\Repository\SituationRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use App\Service\Signalement\CriticiteCalculatorService;
use App\Service\Signalement\PostalCodeHomeChecker;
use App\Service\Signalement\ReferenceGenerator;
use App\Service\Signalement\SignalementQualificationService;
use App\Service\UploadHandlerService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/')]
class FrontSignalementController extends AbstractController
{
    #[Route('/signalement', name: 'front_signalement')]
    public function index(SituationRepository $situationRepository, Request $request): Response
    {
        $title = 'Signalez vos problèmes de logement';
        $etats = ['Etat moyen', 'Mauvais état', 'Très mauvais état'];
        $etats_classes = ['moyen', 'grave', 'tres-grave'];
        $signalement = new Signalement();
        $form = $this->createForm(SignalementType::class);
        $form->handleRequest($request);

        return $this->render('front/signalement.html.twig', [
            'title' => $title,
            'situations' => $situationRepository->findAllActive(),
            'signalement' => $signalement,
            'form' => $form->createView(),
            'etats' => $etats,
            'etats_classes' => $etats_classes,
        ]);
    }

    #[Route('/checkterritory', name: 'front_signalement_check_territory', methods: ['GET'])]
    public function checkTerritory(Request $request, PostalCodeHomeChecker $postalCodeHomeChecker): Response
    {
        $postalCode = $request->get('cp');
        if (empty($postalCode)) {
            return $this->json(['success' => false, 'message' => 'cp parameter is missing'], Response::HTTP_BAD_REQUEST);
        }

        $inseeCode = $request->get('insee');
        if ($postalCodeHomeChecker->isActive($postalCode, $inseeCode)) {
            return $this->json(['success' => true, 'message' => 'Open territory']);
        }

        return $this->json(['success' => false, 'message' => 'Closed territory'], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/signalement/handle', name: 'handle_upload', methods: 'POST')]
    public function handleUpload(UploadHandlerService $uploadHandlerService, Request $request, RequestStack $requestStack, LoggerInterface $logger)
    {
        if (null !== ($files = $request->files->get('signalement'))) {
            try {
                foreach ($files as $key => $file) {
                    return $this->json($uploadHandlerService->toTempFolder($file)->setKey($key));
                }
            } catch (MaxUploadSizeExceededException $exception) {
                $logger->error($exception->getMessage());

                return $this->json(['error' => $exception->getMessage()], 400);
            }
        }
        $logger->error('Un problème lors du téléversement est survenu');

        return $this->json(['error' => 'Aucun fichier n\'a été téléversé'], 400);
    }

    /**
     * @throws Exception
     */
    #[Route('/signalement/envoi', name: 'envoi_signalement', methods: 'POST')]
    public function envoi(
        Request $request,
        ManagerRegistry $doctrine,
        TerritoryRepository $territoryRepository,
        NotificationService $notificationService,
        UploadHandlerService $uploadHandlerService,
        ReferenceGenerator $referenceGenerator,
        PostalCodeHomeChecker $postalCodeHomeChecker,
        EventDispatcherInterface $eventDispatcher,
        UrlGeneratorInterface $urlGenerator,
        CritereRepository $critereRepository
    ): Response {
        if ($this->isCsrfTokenValid('new_signalement', $request->request->get('_token'))
            && $data = $request->get('signalement')) {
            $em = $doctrine->getManager();
            $signalement = new Signalement();
            $files_array = [];

            $dataDateBail = null;
            $dataHasDPE = null;
            $dataDateDPE = null;
            $dataConsoSizeYear = null;
            $dataConsoSize = null;
            $dataConsoYear = null;
            $listNDECriticites = [];

            if (isset($data['files'])) {
                $dataFiles = $data['files'];
                foreach ($dataFiles as $key => $files) {
                    foreach ($files as $titre => $file) {
                        $files_array[$key][] = ['file' => $uploadHandlerService->uploadFromFilename($file), 'titre' => $titre, 'date' => (new DateTimeImmutable())->format('d.m.Y')];
                    }
                }
                unset($data['files']);
            }
            if (isset($files_array['documents'])) {
                $signalement->setDocuments($files_array['documents']);
            }
            if (isset($files_array['photos'])) {
                $signalement->setPhotos($files_array['photos']);
            }
            foreach ($data as $key => $value) {
                $method = 'set'.ucfirst($key);
                switch ($key) {
                    case 'situation':
                        foreach ($data[$key] as $idSituation => $criteres) {
                            $situation = $em->getRepository(Situation::class)->find($idSituation);
                            $signalement->addSituation($situation);
                            foreach ($criteres as $critere) {
                                foreach ($critere as $idCritere => $criticites) {
                                    $critere = $em->getRepository(Critere::class)->find($idCritere);
                                    $signalement->addCritere($critere);
                                    $criticite = $em->getRepository(Criticite::class)->find($data[$key][$idSituation]['critere'][$idCritere]['criticite']);
                                    $signalement->addCriticite($criticite);
                                    if (\in_array(Qualification::NON_DECENCE_ENERGETIQUE, $criticite->getQualification())) {
                                        $listNDECriticites[] = $criticite->getId();
                                    }
                                }
                            }
                        }
                        break;

                    case 'dateEntree':
                        $value = new DateTimeImmutable($value);
                        $signalement->$method($value);
                        break;

                    case 'geoloc':
                        $signalement->setGeoloc(['lat' => $data[$key]['lat'], 'lng' => $data[$key]['lng']]);
                        break;

                    case 'dateBail':
                        $dataDateBail = $value;
                        break;
                    case 'hasDPE':
                        $dataHasDPE = $value;
                        break;
                    case 'dateDPE':
                        $dataDateDPE = $value;
                        break;
                    case 'consoSizeYear':
                        $dataConsoSizeYear = $value;
                        break;
                    case 'consoSize':
                        $dataConsoSize = $value;
                        break;
                    case 'consoYear':
                        $dataConsoYear = $value;
                        break;

                    default:
                        if ('setSignalement' !== $method) {
                            if ('' === $value || ' ' === $value) {
                                $value = null;
                            }
                            $signalement->$method($value);
                        }
                }
            }
            if (!$signalement->getIsNotOccupant()) {
                $signalement->setNomDeclarant(null);
                $signalement->setPrenomDeclarant(null);
                $signalement->setMailDeclarant(null);
                $signalement->setStructureDeclarant(null);
                $signalement->setTelDeclarant(null);
            }

            if (!empty($signalement->getCpOccupant())) {
                $signalement->setTerritory(
                    $territoryRepository->findOneBy([
                    'zip' => $postalCodeHomeChecker->getZipCode($signalement->getCpOccupant()), 'isActive' => 1, ])
                );
            }

            if (null === $signalement->getTerritory()
                || !$postalCodeHomeChecker->isAuthorizedInseeCode(
                    $signalement->getTerritory(),
                    $signalement->getInseeOccupant()
                )
            ) {
                return $this->json(['response' => 'Territory is inactive'], Response::HTTP_BAD_REQUEST);
            }
            $signalement->setReference($referenceGenerator->generate($signalement->getTerritory()));

            $score = new CriticiteCalculatorService($signalement, $critereRepository);
            $signalement->setScoreCreation($score->calculate());
            $signalement->setNewScoreCreation($score->calculateNewCriticite());
            $signalement->setCodeSuivi(md5(uniqid()));

            // Non-décence énergétique
            // Create a SignalementQualification if:
            // - Territory in experimentation : $isExperimentationTerritory
            // - Criticité is NDE : $hasNDECriticite
            // - dateEntree >= 2023 or dataDateBail >= 2023 or dataDateBail "Je ne sais pas"
            $experimentationTerritories = $this->getParameter('experimentation_territory');
            $isExperimentationTerritory = \array_key_exists($signalement->getTerritory()->getZip(), $experimentationTerritories);
            if ($isExperimentationTerritory && \count($listNDECriticites) > 0) {
                $isDateBail2023 = $signalement->getDateEntree()->format('Y') >= 2023 || '2023-01-02' === $dataDateBail || 'Je ne sais pas' === $dataDateBail;
                if ($isDateBail2023) {
                    $signalementQualification = new SignalementQualification();
                    $signalementQualification->setQualification(Qualification::NON_DECENCE_ENERGETIQUE);

                    $qualificationDetails = [
                        'consommation_energie' => null,
                        'DPE' => null,
                        'date_dernier_dpe' => null,
                    ];

                    if ('Je ne sais pas' !== $dataDateBail) {
                        if ($signalement->getDateEntree()->format('Y') >= 2023) {
                            $signalementQualification->setDernierBailAt($signalement->getDateEntree());
                        } elseif (!empty($dataDateBail)) {
                            $signalementQualification->setDernierBailAt(new DateTimeImmutable($dataDateBail));
                        }
                        if (empty($dataConsoSizeYear) && !empty($dataConsoYear) && !empty($dataConsoSize)) {
                            $dataConsoSizeYear = round($dataConsoYear / $dataConsoSize, 2);
                        }

                        $dataHasDPE = ('' === $dataHasDPE) ? null : $dataHasDPE;
                        // TODO : remplacer par DTO Hélène
                        $qualificationDetails = [
                            'consommation_energie' => $dataConsoSizeYear,
                            'DPE' => $dataHasDPE,
                            'date_dernier_dpe' => $dataDateDPE,
                        ];
                    }
                    $signalementQualification->setDetails($qualificationDetails);

                    $qualificationService = new SignalementQualificationService($signalement, $signalementQualification);
                    $signalementQualification->setStatus($qualificationService->updateNDEStatus());
                    $signalementQualification->setCriticites($listNDECriticites);
                    $signalement->addSignalementQualification($signalementQualification);
                    $em->persist($signalementQualification);
                }
            }

            $em->persist($signalement);
            $em->flush();
            !$signalement->getIsProprioAverti() && $attachment = file_exists($this->getParameter('mail_attachment_dir').'ModeleCourrier.pdf') ? $this->getParameter('mail_attachment_dir').'ModeleCourrier.pdf' : null;

            $toRecipients = $signalement->getMailUsagers();
            foreach ($toRecipients as $toRecipient) {
                $notificationService->send(
                    NotificationService::TYPE_CONFIRM_RECEPTION,
                    [$toRecipient],
                    [
                        'signalement' => $signalement,
                        'attach' => $attachment ?? null,
                        'lien_suivi' => $urlGenerator->generate(
                            'front_suivi_signalement',
                            [
                                'code' => $signalement->getCodeSuivi(),
                                'from' => $toRecipient,
                            ],
                            UrlGeneratorInterface::ABSOLUTE_URL
                        ),
                    ],
                    $signalement->getTerritory()
                );
            }

            $eventDispatcher->dispatch(new SignalementCreatedEvent($signalement), SignalementCreatedEvent::NAME);

            return $this->json(['response' => 'success']);
        }

        return $this->json(['response' => 'error'], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/suivre-mon-signalement/{code}', name: 'front_suivi_signalement', methods: 'GET')]
    public function suiviSignalement(
        string $code,
        SignalementRepository $signalementRepository,
        Request $request,
        UserManager $userManager
    ) {
        if ($signalement = $signalementRepository->findOneByCodeForPublic($code)) {
            $fromEmail = $request->get('from');
            /** @var User $userOccupant */
            $userOccupant = $userManager->createUsagerFromSignalement($signalement, UserManager::OCCUPANT);
            /** @var User $userDeclarant */
            $userDeclarant = $userManager->createUsagerFromSignalement($signalement, UserManager::DECLARANT);
            $type = null;
            if ($userOccupant && $fromEmail === $userOccupant->getEmail()) {
                $type = UserManager::OCCUPANT;
            } elseif ($userDeclarant && $fromEmail === $userDeclarant->getEmail()) {
                $type = UserManager::DECLARANT;
            }

            // TODO: Verif info perso pour plus de sécu
            return $this->render('front/suivi_signalement.html.twig', [
                'signalement' => $signalement,
                'email' => $fromEmail,
                'type' => $type,
            ]);
        }
        $this->addFlash('error', 'Le lien utilisé est expiré ou invalide, verifier votre saisie.');

        return $this->redirectToRoute('front_signalement');
    }

    #[Route('/suivre-mon-signalement/{code}/response', name: 'front_suivi_signalement_user_response', methods: 'POST')]
    public function postUserResponse(
        string $code,
        SignalementRepository $signalementRepository,
        UserRepository $userRepository,
        NotificationService $notificationService,
        UploadHandlerService $uploadHandlerService,
        Request $request,
        EntityManagerInterface $entityManager
    ) {
        if ($signalement = $signalementRepository->findOneByCodeForPublic($code)) {
            if ($this->isCsrfTokenValid('signalement_front_response_'.$signalement->getUuid(), $request->get('_token'))) {
                $suivi = new Suivi();
                $suivi->setIsPublic(true);
                $email = $request->get('signalement_front_response')['email'];
                $user = $userRepository->findOneBy(['email' => $email]);
                $suivi->setCreatedBy($user);
                $suivi->setType(Suivi::TYPE_USAGER);

                $description = nl2br(filter_var($request->get('signalement_front_response')['content'], \FILTER_SANITIZE_STRING));
                $files_array = [
                    'documents' => $signalement->getDocuments(),
                    'photos' => $signalement->getPhotos(),
                ];
                $list = [];
                if ($data = $request->get('signalement')) {
                    if (isset($data['files'])) {
                        $dataFiles = $data['files'];
                        foreach ($dataFiles as $key => $files) {
                            foreach ($files as $titre => $file) {
                                $files_array[$key][] = ['file' => $uploadHandlerService->uploadFromFilename($file), 'titre' => $titre, 'date' => (new DateTimeImmutable())->format('d.m.Y')];
                                $list[] = '<li><a class="fr-link" target="_blank" href="'.$this->generateUrl('show_uploaded_file', ['folder' => '_up', 'filename' => $file]).'&t=___TOKEN___">'.$titre.'</a></li>';
                            }
                        }
                        unset($data['files']);
                    }
                    if (!empty($list)) {
                        $description .= '<br>Ajout de pièces au signalement<ul>'.implode('', $list).'</ul>';
                    }
                }

                $signalement->setDocuments($files_array['documents']);
                $signalement->setPhotos($files_array['photos']);
                $suivi->setDescription($description);
                $suivi->setSignalement($signalement);
                $entityManager->persist($suivi);
                $entityManager->flush();
                $this->addFlash('success', "Votre message a bien été envoyé ; vous recevrez un email lorsque votre dossier sera mis à jour. N'hésitez pas à consulter votre page de suivi !");
            }
        } else {
            $this->addFlash('error', 'Une erreur est survenue...');
        }

        if (!empty($email)) {
            return $this->redirectToRoute('front_suivi_signalement', ['code' => $signalement->getCodeSuivi(), 'from' => $email]);
        }

        return $this->redirectToRoute('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()]);
    }
}
