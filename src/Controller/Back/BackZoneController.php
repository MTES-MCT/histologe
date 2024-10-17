<?php

namespace App\Controller\Back;

use App\Entity\Zone;
use App\Form\SearchZoneType;
use App\Form\ZoneType;
use App\Repository\ZoneRepository;
use App\Service\FormHelper;
use App\Service\Import\CsvParser;
use App\Service\SearchZone;
use App\Utils\Json;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/zone')]
#[IsGranted('ROLE_ADMIN_TERRITORY')]
class BackZoneController extends AbstractController
{
    private const MAX_LIST_PAGINATION = 25;

    public function __construct(
        #[Autowire(env: 'FEATURE_ZONAGE')]
        bool $featureExportUsers,
    ) {
        if (!$featureExportUsers) {
            throw $this->createNotFoundException();
        }
    }

    #[Route('/', name: 'back_zone_index', methods: ['GET'])]
    public function index(Request $request, ZoneRepository $zoneRepository): Response
    {
        $searchZone = new SearchZone($this->getUser());
        $form = $this->createForm(SearchZoneType::class, $searchZone);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchZone = new SearchZone($this->getUser());
        }
        $paginatedZones = $zoneRepository->findFilteredPaginated($searchZone, self::MAX_LIST_PAGINATION);

        $zone = new Zone();
        $addForm = $this->createForm(ZoneType::class, $zone, ['action' => $this->generateUrl('back_zone_add')]);

        return $this->render('back/zone/index.html.twig', [
            'form' => $form,
            'addForm' => $addForm, 
            'searchZone' => $searchZone,
            'zones' => $paginatedZones,
            'pages' => (int) ceil($paginatedZones->count() / self::MAX_LIST_PAGINATION),
        ]);
    }

    #[Route('/ajouter', name: 'back_zone_add', methods: 'POST')]
    public function add(Request $request, EntityManagerInterface $em, CsvParser $csvParser): JsonResponse|RedirectResponse {
        $zone = new Zone();
        $form = $this->createForm(ZoneType::class, $zone, ['action' => $this->generateUrl('back_zone_add')]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $errors = FormHelper::getErrorsFromForm($form);
            $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => $errors];

            return $this->json($response, $response['code']);
        }
        if ($form->isSubmitted()) {
            $file = $form->get('file')->getData();
            $csv = $csvParser->parseAsDict($file);
            $csvLine = $csv[0];
            if(!isset($csvLine['WKT'])) {
                $form->get('file')->addError(new FormError('Le fichier doit contenir une colonne "WKT"'));
            }else if(empty($csvLine['WKT'])){
                $form->get('file')->addError(new FormError('La colonne "WKT" ne doit pas être vide'));
            }
            if(!$form->isValid()){
                $errors = FormHelper::getErrorsFromForm($form);
                $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => $errors];
                return $this->json($response, $response['code']);
            }
            $zone->setArea($csvLine['WKT']);
            $zone->setCreatedBy($this->getUser());
            $em->persist($zone);
            $em->flush();

            $this->addFlash('success', 'La zone a bien été ajoutée.');
            return $this->redirectToRoute('back_zone_show', ['zone' => $zone->getId()]);
        }
        return $this->json(['code' => Response::HTTP_OK]);
    }

    #[Route('/{zone}', name: 'back_zone_show', methods: ['GET'])]
    public function show(Zone $zone): Response
    {
        //TODO : voter limitant au territoire du partenaire

        return $this->render('back/zone/show.html.twig', [
            'zone' => $zone,
        ]);
    }

    #[Route('/supprimer/{zone}', name: 'back_zone_delete', methods: ['GET'])]
    public function delete(Zone $zone, EntityManagerInterface $em): RedirectResponse
    {
        //TODO : voter limitant au territoire du partenaire
        $em->remove($zone);
        $em->flush();
        $this->addFlash('success', 'La zone a bien été supprimée.');

        return $this->redirectToRoute('back_zone_index');
    }



}
