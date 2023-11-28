<?php

namespace App\Controller;

use App\Entity\Critere;
use App\Entity\Criticite;
use App\Entity\Enum\Qualification;
use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\Situation;
use App\Entity\Suivi;
use App\Entity\User;
use App\Event\SignalementCreatedEvent;
use App\Factory\FileFactory;
use App\Factory\SignalementQualificationFactory;
use App\Factory\SuiviFactory;
use App\Form\SignalementType;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Repository\SignalementRepository;
use App\Repository\SituationRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Service\Files\DocumentProvider;
use App\Service\ImageManipulationHandler;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\CriticiteCalculator;
use App\Service\Signalement\PostalCodeHomeChecker;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use App\Service\Signalement\QualificationStatusService;
use App\Service\Signalement\ReferenceGenerator;
use App\Service\Signalement\SignalementFileProcessor;
use App\Service\Signalement\ZipcodeProvider;
use App\Service\UploadHandlerService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
    public function handleUpload(
        UploadHandlerService $uploadHandlerService,
        Request $request,
        LoggerInterface $logger,
        ImageManipulationHandler $imageManipulationHandler
    ) {
        if (null !== ($files = $request->files->get('signalement'))) {
            try {
                foreach ($files as $key => $file) {
                    $res = $uploadHandlerService->toTempFolder($file)->setKey($key);
                    if (!isset($res['error']) && \in_array($file->getMimeType(), ImageManipulationHandler::IMAGE_MIME_TYPES)) {
                        $imageManipulationHandler->resize($res['filePath'])->thumbnail();
                    }

                    return $this->json($res);
                }
            } catch (\Exception $exception) {
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
        EntityManagerInterface $entityManager,
        TerritoryRepository $territoryRepository,
        NotificationMailerRegistry $notificationMailerRegistry,
        UploadHandlerService $uploadHandlerService,
        ReferenceGenerator $referenceGenerator,
        PostalCodeHomeChecker $postalCodeHomeChecker,
        ZipcodeProvider $zipcodeProvider,
        EventDispatcherInterface $eventDispatcher,
        SignalementQualificationFactory $signalementQualificationFactory,
        QualificationStatusService $qualificationStatusService,
        ValidatorInterface $validator,
        SignalementQualificationUpdater $signalementQualificationUpdater,
        CriticiteCalculator $criticiteCalculator,
        FileFactory $fileFactory,
        LoggerInterface $logger,
        DocumentProvider $documentProvider
    ): Response {
        if ($this->isCsrfTokenValid('new_signalement', $request->request->get('_token'))
            && $data = $request->get('signalement')) {
            $signalement = new Signalement();
            $dataDateBail = $dataHasDPE = $dataDateDPE = $dataConsoSizeYear = $dataConsoSize = $dataConsoYear = null;
            $listNDECriticites = [];

            if (isset($data['files'])) {
                $dataFiles = $data['files'];
                foreach ($dataFiles as $key => $files) {
                    foreach ($files as $titre => $file) {
                        $filename = $uploadHandlerService->moveFromBucketTempFolder($file);
                        $file = $fileFactory->createInstanceFrom(
                            filename: $filename,
                            title: $titre,
                            type: 'documents' === $key ? File::FILE_TYPE_DOCUMENT : File::FILE_TYPE_PHOTO,
                        );
                        if (null !== $file) {
                            $file->setSize($uploadHandlerService->getFileSize($file->getFilename()));
                            $file->setIsVariantsGenerated($uploadHandlerService->hasVariants($file->getFilename()));
                            $signalement->addFile($file);
                        }
                    }
                }
                unset($data['files']);
            }

            foreach ($data as $key => $value) {
                $method = 'set'.ucfirst($key);
                switch ($key) {
                    case 'situation':
                        foreach ($data[$key] as $idSituation => $criteres) {
                            $situation = $entityManager->getRepository(Situation::class)->find($idSituation);
                            $signalement->addSituation($situation);
                            foreach ($criteres as $critere) {
                                foreach ($critere as $idCritere => $criticites) {
                                    $critere = $entityManager->getRepository(Critere::class)->find($idCritere);
                                    $signalement->addCritere($critere);
                                    $criticite = $entityManager->getRepository(Criticite::class)->find(
                                        $data[$key][$idSituation]['critere'][$idCritere]['criticite']
                                    );
                                    $signalement->addCriticite($criticite);
                                    // TODO : replace getQualification with an array of enum
                                    if (null !== $criticite->getQualification() && \in_array(Qualification::NON_DECENCE_ENERGETIQUE->value, $criticite->getQualification())) {
                                        $listNDECriticites[] = $criticite->getId();
                                    }
                                }
                            }
                        }
                        break;

                    case 'dateEntree':
                        if (!empty($value)) {
                            $value = new DateTimeImmutable($value);
                            $signalement->$method($value);
                        }
                        break;

                    case 'dateNaissanceOccupant':
                        $year = trim($value['year']);
                        $month = trim($value['month']);
                        $day = trim($value['day']);
                        if ('' !== $year && '' !== $month && '' !== $day) {
                            $value = new DateTimeImmutable($year.'-'.$month.'-'.$day);
                            $signalement->$method($value);
                        }
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
            $errors = $validator->validate($signalement);

            if (\count($errors) > 0) {
                $errsMsgList = [];
                foreach ($errors as $error) {
                    $errsMsgList[$error->getPropertyPath().'_'.uniqid()] = $error->getMessage();
                }

                return $this->json(
                    [
                        'response' => 'formErrors',
                        'errsMsgList' => $errsMsgList,
                    ],
                );
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
                        'zip' => $zipcodeProvider->getZipCode($signalement->getCpOccupant()), 'isActive' => 1, ])
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

            $signalement->setScore($criticiteCalculator->calculate($signalement));
            $signalementQualificationUpdater->updateQualificationFromScore($signalement);
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
                    $signalementQualification = $signalementQualificationFactory->createNDEInstanceFrom(
                        signalement: $signalement,
                        listNDECriticites: $listNDECriticites,
                        dataDateBail: $dataDateBail,
                        dataConsoSizeYear: $dataConsoSizeYear,
                        dataConsoYear: $dataConsoYear,
                        dataConsoSize: $dataConsoSize,
                        dataHasDPE: $dataHasDPE,
                        dataDateDPE: $dataDateDPE
                    );

                    $signalement->addSignalementQualification($signalementQualification);
                    // redéfinit le statut de la qualification après sa création
                    $signalementQualification->setStatus($qualificationStatusService->getNDEStatus($signalementQualification));
                    $entityManager->persist($signalementQualification);
                }
            }

            $entityManager->persist($signalement);
            $entityManager->flush();

            $toRecipients = $signalement->getMailUsagers();
            foreach ($toRecipients as $toRecipient) {
                $notificationMailerRegistry->send(
                    new NotificationMail(
                        type: NotificationMailerType::TYPE_CONFIRM_RECEPTION,
                        to: $toRecipient,
                        territory: $signalement->getTerritory(),
                        signalement: $signalement,
                        attachment: $documentProvider->getModeleCourrierPourProprietaire($signalement),
                    )
                );
            }

            $eventDispatcher->dispatch(new SignalementCreatedEvent($signalement), SignalementCreatedEvent::NAME);

            return $this->json(['response' => 'success']);
        }

        $logger->error(
            'Erreur lors de l\'enregistrement du signalement : {payload}',
            ['payload' => $request->request->all()]
        );

        return $this->json(['response' => 'error'], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/suivre-ma-procedure/{code}', name: 'front_suivi_procedure', methods: 'GET')]
    public function suiviProcedure(
        string $code,
        SignalementRepository $signalementRepository,
        Request $request,
        UserManager $userManager,
        SuiviManager $suiviManager,
    ) {
        if ($signalement = $signalementRepository->findOneByCodeForPublic($code)) {
            $requestEmail = $request->get('from');
            $fromEmail = \is_array($requestEmail) ? array_pop($requestEmail) : $requestEmail;
            $suiviAuto = $request->get('suiviAuto');

            /** @var User $userOccupant */
            $userOccupant = $userManager->createUsagerFromSignalement($signalement, UserManager::OCCUPANT);
            /** @var User $userDeclarant */
            $userDeclarant = $userManager->createUsagerFromSignalement($signalement, UserManager::DECLARANT);
            $type = null;
            $user = null;
            if ($userOccupant && $fromEmail === $userOccupant->getEmail()) {
                $type = UserManager::OCCUPANT;
                $user = $userOccupant;
            } elseif ($userDeclarant && $fromEmail === $userDeclarant->getEmail()) {
                $type = UserManager::DECLARANT;
                $user = $userDeclarant;
            }
            if ($user && $suiviAuto) {
                if ($signalement->getIsUsagerAbandonProcedure()) {
                    $this->addFlash('error', 'Les services ont déjà été informés de votre volonté d\'arrêter la procédure.
                    Si vous le souhaitez, vous pouvez préciser la raison de l\'arrêt de procédure
                    en envoyant un message via le formulaire ci-dessous.');

                    return $this->redirectToRoute(
                        'front_suivi_signalement',
                        ['code' => $signalement->getCodeSuivi(), 'from' => $fromEmail]
                    );
                }

                if (Suivi::POURSUIVRE_PROCEDURE === $suiviAuto) {
                    $description = $user->getNomComplet().' ('.$type.') a indiqué vouloir poursuivre la procédure.';
                    $suiviPoursuivreProcedure = $suiviManager->findOneBy([
                        'description' => $description,
                        'signalement' => $signalement,
                    ]);
                    if (null !== $suiviPoursuivreProcedure) {
                        $this->addFlash('error', 'Les services ont déjà été informés de votre volonté de continuer la procédure.
                        Si vous le souhaitez, vous pouvez envoyer un message via le formulaire ci-dessous.');

                        return $this->redirectToRoute(
                            'front_suivi_signalement',
                            ['code' => $signalement->getCodeSuivi(), 'from' => $fromEmail]
                        );
                    }
                }

                return $this->render('front/suivi_signalement.html.twig', [
                    'signalement' => $signalement,
                    'email' => $fromEmail,
                    'type' => $type,
                    'suiviAuto' => $suiviAuto,
                ]);
            }

            return $this->redirectToRoute('front_suivi_signalement');
        }
        $this->addFlash('error', 'Le lien utilisé est expiré ou invalide, verifier votre saisie.');

        return $this->redirectToRoute('front_signalement');
    }

    #[Route('/suivre-mon-signalement/{code}', name: 'front_suivi_signalement', methods: 'GET')]
    public function suiviSignalement(
        string $code,
        SignalementRepository $signalementRepository,
        Request $request,
        UserManager $userManager,
        EntityManagerInterface $entityManager,
        SuiviFactory $suiviFactory,
    ) {
        if ($signalement = $signalementRepository->findOneByCodeForPublic($code)) {
            $requestEmail = $request->get('from');
            $fromEmail = \is_array($requestEmail) ? array_pop($requestEmail) : $requestEmail;
            $suiviAuto = $request->get('suiviAuto');

            /** @var User $userOccupant */
            $userOccupant = $userManager->createUsagerFromSignalement($signalement, UserManager::OCCUPANT);
            /** @var User $userDeclarant */
            $userDeclarant = $userManager->createUsagerFromSignalement($signalement, UserManager::DECLARANT);
            $type = null;
            $user = null;
            if ($userOccupant && $fromEmail === $userOccupant->getEmail()) {
                $type = UserManager::OCCUPANT;
                $user = $userOccupant;
            } elseif ($userDeclarant && $fromEmail === $userDeclarant->getEmail()) {
                $type = UserManager::DECLARANT;
                $user = $userDeclarant;
            }
            if ($user && $suiviAuto) {
                $description = '';
                if (Suivi::ARRET_PROCEDURE === $suiviAuto) {
                    $description = $user->getNomComplet().' ('.$type.') a demandé l\'arrêt de la procédure.';
                    $signalement->setIsUsagerAbandonProcedure(true);
                    $entityManager->persist($signalement);
                }
                if (Suivi::POURSUIVRE_PROCEDURE === $suiviAuto) {
                    $description = $user->getNomComplet().' ('.$type.') a indiqué vouloir poursuivre la procédure.';
                }

                $params = [
                    'type' => SUIVI::TYPE_USAGER,
                    'description' => $description,
                ];

                $suivi = $suiviFactory->createInstanceFrom(
                    $user,
                    $signalement,
                    $params,
                    true
                );
                $entityManager->persist($suivi);
                $entityManager->flush();
                if (Suivi::ARRET_PROCEDURE === $suiviAuto) {
                    $this->addFlash('success', "Les services ont été informés de votre volonté d'arrêter la procédure.
                Si vous le souhaitez, vous pouvez préciser la raison de l'arrêt de procédure
                en envoyant un message via le formulaire ci-dessous.");
                }
                if (Suivi::POURSUIVRE_PROCEDURE === $suiviAuto) {
                    $this->addFlash('success', "Les services ont été informés de votre volonté de poursuivre la procédure.
                N'hésitez pas à mettre à jour votre situation en envoyant un message via le formulaire ci-dessous.");
                }
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
        Request $request,
        EntityManagerInterface $entityManager,
        SuiviFactory $suiviFactory,
        SignalementFileProcessor $signalementFileProcessor,
    ): RedirectResponse {
        if ($signalement = $signalementRepository->findOneByCodeForPublic($code)) {
            if ($this->isCsrfTokenValid('signalement_front_response_'.$signalement->getUuid(), $request->get('_token'))) {
                $email = $request->get('signalement_front_response')['email'];
                $user = $userRepository->findOneBy(['email' => $email]);
                $suivi = $suiviFactory->createInstanceFrom(
                    user: $user,
                    signalement: $signalement,
                    params: ['type' => Suivi::TYPE_USAGER],
                    isPublic: true,
                );

                $description = htmlspecialchars(
                    nl2br($request->get('signalement_front_response')['content']),
                    \ENT_QUOTES,
                    'UTF-8'
                );

                $fileList = $descriptionList = [];
                if ($data = $request->get('signalement')) {
                    if (isset($data['files'])) {
                        $dataFiles = $data['files'];
                        foreach ($dataFiles as $inputName => $files) {
                            list($files, $descriptions) = $signalementFileProcessor->process($dataFiles, $inputName);
                            $fileList = [...$fileList, ...$files];
                            $descriptionList = [...$descriptionList, ...$descriptions];
                        }
                        unset($data['files']);
                    }
                    if (!empty($descriptionList)) {
                        $description .= '<br>Ajout de pièces au signalement<ul>'
                            .implode('', $descriptionList).'</ul>';
                        $signalementFileProcessor->addFilesToSignalement($fileList, $signalement);
                    }
                }

                $suivi->setDescription($description);
                $entityManager->persist($suivi);
                $entityManager->flush();
                $this->addFlash('success', <<<SUCCESS
                Votre message a bien été envoyé, vous recevrez un email lorsque votre dossier sera mis à jour.
                N'hésitez pas à consulter votre page de suivi !
                SUCCESS);
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
