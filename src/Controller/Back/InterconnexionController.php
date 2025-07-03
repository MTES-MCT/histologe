<?php

namespace App\Controller\Back;

use App\Form\SearchInterconnexionType;
use App\Repository\JobEventRepository;
use App\Repository\PartnerRepository;
use App\Service\ListFilters\SearchInterconnexion;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/connexions-si')]
class InterconnexionController extends AbstractController
{
    #[Route('/', name: 'back_interconnexion_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        Request $request,
        JobEventRepository $jobEventRepository,
        PartnerRepository $partnerRepository,
        ParameterBagInterface $parameterBag,
    ): Response {
        $searchInterconnexion = new SearchInterconnexion();
        $form = $this->createForm(SearchInterconnexionType::class, $searchInterconnexion);

        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchInterconnexion = new SearchInterconnexion();
        }
        // $maxListPagination = $parameterBag->get('standard_max_list_pagination');
        $maxListPagination = 5;
        $territoryId = null;
        $territory = $searchInterconnexion->getTerritory();
        if ($territory && is_object($territory) && method_exists($territory, 'getId')) {
            $territoryId = $territory->getId();
        } elseif (is_int($territory)) {
            $territoryId = $territory;
        }
        $partner = $searchInterconnexion->getPartner();
        $partnerId = null;
        if ($partner && is_object($partner) && method_exists($partner, 'getId')) {
            $partnerId = $partner->getId();
        } elseif (is_int($partner)) {
            $partnerId = $partner;
        }
        $status = $searchInterconnexion->getStatus();
        $orderType = $searchInterconnexion->getOrderType() ?? 'createdAt-DESC';
        $page = $searchInterconnexion->getPage() ?? 1;

        $territories = $territoryId ? [$territoryId] : [];
        $params = ['period' => 90];
        $allConnections = $jobEventRepository->findLastJobEventByTerritory(
            $params['period'],
            $territories
        );
        $connections = array_filter($allConnections, function ($conn) use ($partnerId, $status) {
            if ($partnerId && $conn['id'] != $partnerId) {
                return false;
            }
            if ($status && $conn['status'] != $status) {
                return false;
            }

            return true;
        });
        usort($connections, function ($a, $b) use ($orderType) {
            if ('createdAt-ASC' === $orderType) {
                return $a['createdAt'] <=> $b['createdAt'];
            }

            return $b['createdAt'] <=> $a['createdAt'];
        });
        $total = count($connections);
        $pages = (int) ceil($total / $maxListPagination);
        $connections = array_slice($connections, ($page - 1) * $maxListPagination, $maxListPagination);
        $partnerMap = [];
        foreach ($partnerRepository->findAll() as $p) {
            $partnerMap[$p->getId()] = $p->getNom();
        }
        $connections = array_map(function ($conn) use ($partnerMap) {
            return [
                'signalementReference' => $conn['reference'] ?? '',
                'createdAt' => $conn['createdAt'],
                'partnerName' => $conn['nom'] ?? ($partnerMap[$conn['id']] ?? ''),
                'service' => $conn['service'] ?? '',
                'action' => $conn['action'] ?? '',
                'status' => $conn['status'] ?? '',
                'response' => $conn['response'] ?? '',
            ];
        }, $connections);

        /*  dump('ici');
          $paginatedConnections = $jobEventRepository->findFilteredPaginated(
              $searchInterconnexion,
              90,
              $maxListPagination
          );
          dump($paginatedConnections->count());
          dump($paginatedConnections);
          dump((int) ceil($paginatedConnections->count() / $maxListPagination));*/
        return $this->render('back/interconnexion/index.html.twig', [
            'form' => $form,
            'searchInterconnexion' => $searchInterconnexion,
            'connections' => $connections,
            'pages' => $pages,
            // 'connections' => $paginatedConnections,
            // 'pages' => (int) ceil($paginatedConnections->count() / $maxListPagination),
        ]);
    }
}
