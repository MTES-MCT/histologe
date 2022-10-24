<?php

namespace App\Controller;

use App\Entity\Critere;
use App\Entity\Criticite;
use App\Entity\Signalement;
use App\Entity\Situation;
use App\Entity\Suivi;
use App\Exception\MaxUploadSizeExceededException;
use App\Form\SignalementType;
use App\Repository\SignalementRepository;
use App\Repository\SituationRepository;
use App\Repository\TerritoryRepository;
use App\Service\CriticiteCalculatorService;
use App\Service\NotificationService;
use App\Service\Signalement\PostalCodeHomeChecker;
use App\Service\Signalement\ReferenceGenerator;
use App\Service\UploadHandlerService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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

        if ($postalCodeHomeChecker->isActive($postalCode)) {
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
        PostalCodeHomeChecker $postalCodeHomeChecker): Response
    {
        if ($data = $request->get('signalement')) {
            $em = $doctrine->getManager();
            $signalement = new Signalement();
            $files_array = [];

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

            $signalement->setTerritory($territoryRepository->findOneBy([
                'zip' => $postalCodeHomeChecker->mapZip($signalement->getCpOccupant()), 'isActive' => 1, ])
            );

            if (null === $signalement->getTerritory()) {
                return $this->json(['response' => 'Territory is inactive'], Response::HTTP_BAD_REQUEST);
            }
            $signalement->setReference($referenceGenerator->generate($signalement->getTerritory()));

            $score = new CriticiteCalculatorService($signalement, $doctrine);
            $signalement->setScoreCreation($score->calculate());

            $em->persist($signalement);
            $em->flush();
            !$signalement->getIsProprioAverti() && $attachment = file_exists($this->getParameter('mail_attachment_dir').'ModeleCourrier.pdf') ? $this->getParameter('mail_attachment_dir').'ModeleCourrier.pdf' : null;
            $notificationService->send(NotificationService::TYPE_CONFIRM_RECEPTION, [$signalement->getMailDeclarant(), $signalement->getMailOccupant()], ['signalement' => $signalement, 'attach' => $attachment ?? null], $signalement->getTerritory());

            return $this->json(['response' => 'success']);
        }

        return $this->json(['response' => 'error'], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/suivre-mon-signalement/{code}', name: 'front_suivi_signalement', methods: 'GET')]
    public function suiviSignalement(string $code, SignalementRepository $signalementRepository)
    {
        if ($signalement = $signalementRepository->findOneByCodeForPublic($code)) {
            // TODO: Verif info perso pour plus de sécu
            return $this->render('front/suivi_signalement.html.twig', [
                'signalement' => $signalement,
            ]);
        }
        $this->addFlash('error', 'Le lien utilisé est expiré ou invalide, verifier votre saisie.');

        return $this->redirectToRoute('front_signalement');
    }

    #[Route('/suivre-mon-signalement/{code}/response', name: 'front_suivi_signalement_user_response', methods: 'POST')]
    public function postUserResponse(
        string $code,
        SignalementRepository $signalementRepository,
        NotificationService $notificationService,
        UploadHandlerService $uploadHandlerService,
        Request $request,
        EntityManagerInterface $entityManager)
    {
        if ($signalement = $signalementRepository->findOneByCodeForPublic($code)) {
            if ($this->isCsrfTokenValid('signalement_front_response_'.$signalement->getUuid(), $request->get('_token'))) {
                $suivi = new Suivi();
                $suivi->setIsPublic(true);
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

        return $this->redirectToRoute('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()]);
    }
}
