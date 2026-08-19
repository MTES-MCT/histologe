<?php

namespace App\Controller\Back;

use App\Form\RialType;
use App\Form\TopoSearchType;
use App\Service\Gouv\Rial\RialService;
use App\Service\Gouv\Topo\TopoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/tools/rial')]
#[IsGranted('ROLE_ADMIN')]
class RialToolController extends AbstractController
{
    #[Route('/', name: 'back_tools_rial', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        RialService $rialService,
        TopoService $topoService,
        #[Autowire(env: 'RIAL_ENABLE')]
        bool $rialEnable,
    ): Response {
        if (!$rialEnable) {
            return $this->render('back/tools/rial.html.twig');
        }

        $session = $request->getSession();
        if ($request->isMethod('GET')) {
            $session->remove('rial_topo_searches');
        }

        $rialForm = $this->createForm(RialType::class);
        $rialForm->handleRequest($request);

        $submittedTopoForm = $this->createForm(TopoSearchType::class);
        $submittedTopoForm->handleRequest($request);

        $submittedBanId = null;
        if ($submittedTopoForm->isSubmitted()) {
            $submittedTopoData = $submittedTopoForm->getData();
            $submittedBanId = $submittedTopoData['ban_id'] ?? null;
        } elseif ($rialForm->isSubmitted()) {
            $session->remove('rial_topo_searches');
        }

        $storedTopoSearches = $session->get('rial_topo_searches', []);
        if (!\is_array($storedTopoSearches)) {
            $storedTopoSearches = [];
        }

        $results = [];
        $topoSearches = [];
        $totalFiscalCount = 0;

        if ($rialForm->isSubmitted() && $rialForm->isValid()) {
            $banIdsRaw = $rialForm->get('banIds')->getData() ?? '';
            $parts = preg_split('/[\s,]+/', $banIdsRaw);
            $banIds = array_filter(array_map('trim', $parts ?: []));
            foreach ($banIds as $banId) {
                try {
                    $identifiantsFiscaux = $rialService->searchLocauxByBanId($banId) ?? [];
                    $analysis = $this->analyzeBanId($banId);

                    if ($analysis['is_pseudo_code']) {
                        $topoSearches[$banId] = $analysis;
                    }

                    if (empty($identifiantsFiscaux)) {
                        $results[] = [
                            'ban_id' => $banId,
                            'identifiant_fiscal' => 'Aucun identifiant fiscal pour cet identifiant BAN',
                            'local_data' => '',
                            'is_pseudo_code' => $analysis['is_pseudo_code'],
                        ];
                        continue;
                    }
                    foreach ($identifiantsFiscaux as $identifiantFiscal) {
                        ++$totalFiscalCount;
                        $localData = $rialService->searchLocalByIdFiscal($identifiantFiscal);
                        $results[] = [
                            'ban_id' => $banId,
                            'identifiant_fiscal' => $identifiantFiscal,
                            'local_data' => $localData ? json_encode($localData, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE) : 'Aucune info pour cet identifiant fiscal',
                            'is_pseudo_code' => $analysis['is_pseudo_code'],
                        ];
                    }
                } catch (\Throwable) {
                    $results[] = [
                        'ban_id' => $banId,
                        'identifiant_fiscal' => 'ERROR BAN',
                        'local_data' => '',
                        'is_pseudo_code' => false,
                    ];
                }
            }
        }

        if ($submittedBanId && $submittedTopoForm->isValid()) {
            $topoData = $submittedTopoForm->getData();
            $topoResults = $topoService->searchVoies(
                $topoData['code_dep'],
                $topoData['code_commune'],
                $topoData['libelle']
            );
            $storedTopoSearches[$submittedBanId] = [
                'libelle' => $topoData['libelle'],
                'results' => $topoResults,
            ];
            $session->set('rial_topo_searches', $storedTopoSearches);
        }

        $topoForms = [];
        foreach ($topoSearches as $banId => $topoSearchData) {
            if (isset($storedTopoSearches[$banId])) {
                $topoSearches[$banId]['libelle'] = $storedTopoSearches[$banId]['libelle'];
                $topoSearches[$banId]['results'] = $storedTopoSearches[$banId]['results'];
                $topoSearches[$banId]['searched'] = true;
            }

            if ($submittedBanId === $banId) {
                $topoForms[$banId] = $submittedTopoForm->createView();
            } else {
                $initialData = $topoSearchData;
                if (isset($storedTopoSearches[$banId]['libelle'])) {
                    $initialData['libelle'] = $storedTopoSearches[$banId]['libelle'];
                }
                $topoForm = $this->createForm(TopoSearchType::class, $initialData);
                $topoForms[$banId] = $topoForm->createView();
            }
        }

        return $this->render('back/tools/rial.html.twig', [
            'rialForm' => $rialForm->createView(),
            'results' => $results,
            'topoSearches' => $topoSearches,
            'topoForms' => $topoForms,
            'submittedBanId' => $submittedBanId,
            'totalFiscalCount' => $totalFiscalCount,
        ]);
    }

    /**
     * @return array{is_pseudo_code: bool, ban_id?: string, code_dep?: string, code_commune?: string}
     */
    private function analyzeBanId(string $banId): array
    {
        $banParts = explode('_', $banId);
        $isPseudoCode = \count($banParts) >= 2 && \strlen($banParts[1]) > 4;

        if (!$isPseudoCode) {
            return ['is_pseudo_code' => false];
        }

        $insee = $banParts[0];
        $codeDep = $this->extractDepartement($insee);

        return [
            'is_pseudo_code' => true,
            'ban_id' => $banId,
            'code_dep' => $codeDep,
            'code_commune' => $this->extractCommune($insee, $codeDep),
        ];
    }

    private function extractDepartement(string $insee): string
    {
        return str_starts_with($insee, '97') ? substr($insee, 0, 3) : substr($insee, 0, 2);
    }

    private function extractCommune(string $insee, string $codeDep): string
    {
        return substr($insee, \strlen($codeDep));
    }
}
