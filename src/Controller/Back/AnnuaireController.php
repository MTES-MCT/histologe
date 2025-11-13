<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Form\SearchAnnuaireAgentType;
use App\Repository\UserPartnerRepository;
use App\Service\ListFilters\SearchAnnuaireAgent;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo/annuaire')]
class AnnuaireController extends AbstractController
{
    public function __construct(
        private readonly UserPartnerRepository $userPartnerRepository,
        #[Autowire(param: 'standard_max_list_pagination')]
        private readonly int $maxListPagination,
        #[Autowire(env: 'FEATURE_ANNUAIRE')]
        bool $featureAnnuaire,
    ) {
        if (!$featureAnnuaire) {
            throw $this->createNotFoundException();
        }
    }

    #[Route('/', name: 'back_annuaire_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        [$form, $search, $userPartners] = $this->handleSearch($request);

        return $this->render('back/annuaire/index.html.twig', [
            'form' => $form,
            'search' => $search,
            'userPartners' => $userPartners,
            'pages' => (int) ceil($userPartners->count() / $this->maxListPagination),
        ]);
    }

    #[Route('/export', name: 'back_annuaire_export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        [$form, $search, $userPartners] = $this->handleSearch($request, false);
        /** @var User $user */
        $user = $this->getUser();
        $isMultiTerritory = false;
        if ($user->isSuperAdmin() || count($user->getPartnersTerritories()) > 1) {
            $isMultiTerritory = true;
        }

        $spreadsheet = new Spreadsheet();
        $activeWorksheet = $spreadsheet->getActiveSheet();

        $activeWorksheet->setCellValue('A1', 'Nom complet de l\'agent');
        $activeWorksheet->setCellValue('B1', 'Nom du partenaire');
        $activeWorksheet->setCellValue('C1', 'Email de l\'agent');
        $activeWorksheet->setCellValue('D1', 'Téléphone de l\'agent');
        $activeWorksheet->setCellValue('E1', 'Fonction de l\'agent');
        if ($isMultiTerritory) {
            $activeWorksheet->setCellValue('F1', 'Territoire');
        }

        $row = 2;
        foreach ($userPartners as $userPartner) {
            $partner = $userPartner->getPartner();
            $user = $userPartner->getUser();
            $activeWorksheet->setCellValue('A'.$row, $user->getNomComplet());
            $activeWorksheet->setCellValue('B'.$row, $partner->getNom());
            $activeWorksheet->setCellValue('C'.$row, $user->getEmail());
            $activeWorksheet->setCellValueExplicit('D'.$row, $user->getPhoneDecoded(), DataType::TYPE_STRING);
            $activeWorksheet->setCellValue('E'.$row, $user->getFonction());
            if ($isMultiTerritory) {
                $territory = $partner->getTerritory();
                $territoryName = $territory ? $territory->getZip().' - '.$territory->getName() : '';
                $activeWorksheet->setCellValue('F'.$row, $territoryName);
            }
            ++$row;
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'annuaire_'.date('Y-m-d_H-i-s').'.xlsx';

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        if (!$content) {
            throw new \RuntimeException('Erreur lors de la génération du contenu CSV.');
        }
        $response = new Response($content);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
        $response->headers->set('Content-Length', (string) strlen($content));

        return $response;
    }

    /**
     * @return array<mixed>
     */
    private function handleSearch(Request $request, bool $paginated = true): array
    {
        /** @var User $user */
        $user = $this->getUser();
        $search = new SearchAnnuaireAgent($user);
        $form = $this->createForm(SearchAnnuaireAgentType::class, $search);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $search = new SearchAnnuaireAgent($user);
        }
        if ($paginated) {
            $userPartners = $this->userPartnerRepository->findAnnuaireAgentPaginated($search, $this->maxListPagination);
        } else {
            $userPartners = $this->userPartnerRepository->findAnnuaireAgent($search);
        }

        return [$form, $search, $userPartners];
    }
}
