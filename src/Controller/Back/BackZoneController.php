<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Entity\Zone;
use App\Form\SearchZoneType;
use App\Form\ZoneType;
use App\Repository\ZoneRepository;
use App\Security\Voter\ZoneVoter;
use App\Service\FormHelper;
use App\Service\Import\CsvParser;
use App\Service\ListFilters\SearchZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/gerer-territoire/zone')]
#[IsGranted('ROLE_ADMIN_TERRITORY')]
class BackZoneController extends AbstractController
{
    #[Route('/', name: 'back_territory_management_zone_index', methods: ['GET'])]
    public function index(
        Request $request,
        ZoneRepository $zoneRepository,
        #[Autowire(param: 'standard_max_list_pagination')] int $maxListPagination,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $searchZone = new SearchZone($user);
        $form = $this->createForm(SearchZoneType::class, $searchZone);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchZone = new SearchZone($user);
        }
        $paginatedZones = $zoneRepository->findFilteredPaginated($searchZone, $maxListPagination);

        $zone = new Zone();
        if (!$this->isGranted('ROLE_ADMIN')) {
            $zone->setTerritory($user->getFirstTerritory());
        }
        $addForm = $this->createForm(ZoneType::class, $zone, ['action' => $this->generateUrl('back_territory_management_zone_add')]);

        return $this->render('back/zone/index.html.twig', [
            'form' => $form,
            'addForm' => $addForm,
            'searchZone' => $searchZone,
            'zones' => $paginatedZones,
            'pages' => (int) ceil($paginatedZones->count() / $maxListPagination),
        ]);
    }

    #[Route('/ajouter', name: 'back_territory_management_zone_add', methods: 'POST')]
    public function add(Request $request, EntityManagerInterface $em, CsvParser $csvParser): JsonResponse|RedirectResponse
    {
        $zone = new Zone();
        /** @var User $user */
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN')) {
            $zone->setTerritory($user->getFirstTerritory());
        }
        /** @var Form $form */
        $form = $this->createForm(ZoneType::class, $zone, ['action' => $this->generateUrl('back_territory_management_zone_add')]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => FormHelper::getErrorsFromForm($form)];

            return $this->json($response, $response['code']);
        }
        if ($form->isSubmitted()) {
            $file = $form->get('file')->getData();
            if ($file) {
                $this->manageCsvFileFormErrors($file, $zone, $form, $csvParser);
            }
            $this->validateArea($zone, $form, $em);
            if (!$form->isValid()) {
                $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => FormHelper::getErrorsFromForm($form)];

                return $this->json($response, $response['code']);
            }
            $zone->setCreatedBy($user);
            $em->persist($zone);
            $em->flush();

            $this->addFlash('success', 'La zone a bien été ajoutée.');

            return $this->redirectToRoute('back_territory_management_zone_show', ['zone' => $zone->getId()]);
        }

        return $this->json(['code' => Response::HTTP_OK]);
    }

    #[Route('/editer/{zone}', name: 'back_territory_management_zone_edit', methods: ['GET', 'POST'])]
    public function edit(Zone $zone, Request $request, EntityManagerInterface $em, CsvParser $csvParser): Response
    {
        $this->denyAccessUnlessGranted(ZoneVoter::MANAGE, $zone);

        /** @var Form $form */
        $form = $this->createForm(ZoneType::class, $zone);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            if ($file) {
                $this->manageCsvFileFormErrors($file, $zone, $form, $csvParser);
            }
            $this->validateArea($zone, $form, $em);
            if ($form->isValid()) {
                $em->flush();
                $this->addFlash('success', 'La zone a bien été modifiée.');

                return $this->redirectToRoute('back_territory_management_zone_show', ['zone' => $zone->getId()]);
            }
        }

        return $this->render('back/zone/edit.html.twig', [
            'form' => $form,
            'zone' => $zone,
        ]);
    }

    #[Route('/{zone}', name: 'back_territory_management_zone_show', methods: ['GET'])]
    public function show(Zone $zone, ZoneRepository $zoneRepository): Response
    {
        $this->denyAccessUnlessGranted(ZoneVoter::MANAGE, $zone);

        $signalements = $zoneRepository->findSignalementsByZone($zone);

        return $this->render('back/zone/show.html.twig', [
            'zone' => $zone,
            'signalements' => $signalements,
        ]);
    }

    #[Route('/supprimer/{zone}', name: 'back_zone_delete', methods: ['GET'])]
    public function delete(Zone $zone, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $this->denyAccessUnlessGranted(ZoneVoter::MANAGE, $zone);
        if (!$this->isCsrfTokenValid('zone_delete', $request->query->get('_token'))) {
            $this->addFlash('error', 'Le token CSRF est invalide.');

            return $this->redirectToRoute('back_territory_management_zone_index');
        }
        $em->remove($zone);
        $em->flush();
        $this->addFlash('success', 'La zone a bien été supprimée.');

        return $this->redirectToRoute('back_territory_management_zone_index');
    }

    private function manageCsvFileFormErrors(File $file, Zone $zone, Form $form, CsvParser $csvParser): void
    {
        $csv = $csvParser->parseAsDict($file);
        $csvLine = $csv[0];
        if (!isset($csvLine['WKT'])) {
            $form->get('file')->addError(new FormError('Le fichier doit contenir une colonne "WKT"'));
        } elseif (empty($csvLine['WKT'])) {
            $form->get('file')->addError(new FormError('La colonne "WKT" ne doit pas être vide'));
        }
        if ($form->isValid()) {
            $zone->setArea($csvLine['WKT']);
        }
    }

    private function validateArea(Zone $zone, Form $form, EntityManagerInterface $em): void
    {
        try {
            $testSQL = 'SELECT ST_GeomFromText(:area)';
            $em->getConnection()->executeQuery($testSQL, ['area' => $zone->getArea()]);
        } catch (\Exception $e) {
            if ($form->get('file')->getData()) {
                $form->get('file')->addError(new FormError('Le format de la zone n\'est pas valide.'));
            } else {
                $form->get('area')->addError(new FormError('Le format de la zone n\'est pas valide.'));
            }
        }
    }
}
