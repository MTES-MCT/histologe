<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Form\SearchAnnuaireAgentType;
use App\Repository\UserPartnerRepository;
use App\Service\ListFilters\SearchAnnuaireAgent;
use App\Utils\ExportFormat;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Options as CsvOptions;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo/annuaire')]
class AnnuaireController extends AbstractController
{
    public function __construct(
        private readonly UserPartnerRepository $userPartnerRepository,
        #[Autowire(param: 'standard_max_list_pagination')]
        private readonly int $maxListPagination,
    ) {
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

    #[Route('/export', name: 'back_annuaire_export', methods: ['GET', 'POST'])]
    public function exportAnnuaire(
        Request $request,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $isMultiTerritory = false;
        if ($user->isSuperAdmin() || count($user->getPartnersTerritories()) > 1) {
            $isMultiTerritory = true;
        }

        $originalMethod = $request->getMethod();
        $request->setMethod('GET'); // to prevent Symfony ignoring GET data while handlning the form
        [$form, $search, $userPartners] = $this->handleSearch($request, false);

        if ('POST' === $originalMethod) {
            /** @var string $format */
            $format = $request->request->get('file-format');
            if (!in_array($format, [ExportFormat::FORMAT_CSV, ExportFormat::FORMAT_XLSX])) {
                $this->addFlash('error', 'Merci de sélectionner le format de l\'export.');

                return $this->redirectToRoute('back_annuaire_export', $search->getUrlParams());
            }
            if (ExportFormat::FORMAT_CSV === $format) {
                $writer = new CsvWriter(new CsvOptions(FIELD_DELIMITER: ExportFormat::CSV_SEPARATOR));
            } elseif (ExportFormat::FORMAT_XLSX === $format) {
                $writer = new XlsxWriter();
            } else {
                throw new \Exception('Invalid format "'.$format.'"');
            }

            $filename = 'annuaire_'.date('Y-m-d_H-i-s').'.'.$format;

            $contentType = ExportFormat::FORMAT_CSV === $format
                ? 'text/csv'
                : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

            $response = new StreamedResponse(static function () use ($writer, $isMultiTerritory, $userPartners) {
                $writer->openToFile('php://output');
                $writer->addRow(Row::fromValues([
                    'Nom complet de l\'agent',
                    'Nom du partenaire',
                    'Email de l\'agent',
                    'Téléphone de l\'agent',
                    'Fonction de l\'agent',
                    $isMultiTerritory ? 'Territoire' : null,
                ]));
                foreach ($userPartners as $userPartner) {
                    $partner = $userPartner->getPartner();
                    $user = $userPartner->getUser();
                    $writer->addRow(new Row([
                        Cell::fromValue($user->getNomComplet()),
                        Cell::fromValue($partner->getNom()),
                        Cell::fromValue($user->getEmail()),
                        new StringCell($user->getPhoneDecoded() ?? '', null),
                        Cell::fromValue($user->getFonction()),
                        Cell::fromValue($isMultiTerritory ? ($partner->getTerritory()?->getZipAndName() ?? '') : null),
                    ]));
                }
                $writer->close();
            });
            $response->headers->set('Content-Type', $contentType);
            $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

            return $response;
        }

        return $this->render('back/annuaire/export-annuaire.html.twig', [
            'searchAnnuaire' => $search,
            'nbResults' => \count($userPartners),
            'isMultiTerritory' => $isMultiTerritory,
        ]);
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
