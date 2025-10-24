<?php

namespace App\Controller\Back;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo/annuaire')]
class AnnuaireController extends AbstractController
{
    /** @var array<int, Territory> */
    private array $territoriesList;

    public function __construct(
        private readonly PartnerRepository $partnerRepository,
        TerritoryRepository $territoryRepository,
        #[Autowire(env: 'FEATURE_NEW_DASHBOARD')]
        bool $featureNewDashboard,
    ) {
        if (!$featureNewDashboard) {
            throw $this->createNotFoundException();
        }
        $this->territoriesList = $territoryRepository->findAllList();
    }

    #[Route('/', name: 'back_annuaire_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $territories = [];
        $partners = $this->getPartnersFromRequest($request, $territories);

        return $this->render('back/annuaire/index.html.twig', [
            'partners' => $partners,
            'territoriesList' => $this->territoriesList,
            'territories' => $territories,
        ]);
    }

    #[Route('/export', name: 'back_annuaire_export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        $partners = $this->getPartnersFromRequest($request);

        $spreadsheet = new Spreadsheet();
        $activeWorksheet = $spreadsheet->getActiveSheet();

        $activeWorksheet->setCellValue('A1', 'Nom du partenaire');
        $activeWorksheet->setCellValue('B1', 'Nom complet de l\'agent');
        $activeWorksheet->setCellValue('C1', 'Email de l\'agent');

        $row = 2;
        foreach ($partners as $partner) {
            if ($partner->getUsers()->isEmpty()) {
                continue;
            }
            $activeWorksheet->setCellValue('A'.$row, $partner->getNom());
            ++$row;
            foreach ($partner->getUsers() as $user) {
                $activeWorksheet->setCellValue('B'.$row, $user->getNomComplet());
                $activeWorksheet->setCellValue('C'.$row, $user->getEmail());
                ++$row;
            }
        }

        $writer = new Csv($spreadsheet);
        $filename = 'annuaire_'.date('Y-m-d_H-i-s').'.csv';

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
        $response->headers->set('Content-Length', (string) strlen($content));

        return $response;
    }

    /**
     * @param array<Territory> $territories
     *
     * @return array<Partner>
     */
    private function getPartnersFromRequest(Request $request, array &$territories = []): array
    {
        /** @var User $user */
        $user = $this->getUser();
        $territories = [];
        if ($this->isGranted('ROLE_ADMIN')) {
            if ($request->query->has('territory') && isset($this->territoriesList[$request->query->get('territory')])) {
                $territories = [$this->territoriesList[$request->query->get('territory')]];
            }
        } else {
            foreach ($user->getPartners() as $partner) {
                $territories[] = $partner->getTerritory();
            }
        }

        return $this->partnerRepository->findAllByTerritoriesWithAgents($territories);
    }
}
