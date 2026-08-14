<?php

namespace App\Controller\Back;

use App\Service\Gouv\Rial\RialService;
use App\Service\Gouv\Topo\TopoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
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
        FormFactoryInterface $formFactory,
        #[Autowire(env: 'RIAL_ENABLE')]
        bool $rialEnable,
    ): Response {
        if (!$rialEnable) {
            return $this->render('back/tools/rial.html.twig');
        }

        $rialSearchData = ['banIds' => $request->request->all()['rial_search']['banIds'] ?? null];
        $rialForm = $formFactory->createNamedBuilder('rial_search', FormType::class, $rialSearchData, [
            'allow_extra_fields' => true,
        ])
            ->add('banIds', TextareaType::class, [
                'label' => 'BAN id(s) (séparés par des virgules ou des retours à la ligne)',
                'required' => true,
            ])
            ->add('submit', SubmitType::class, ['label' => 'Rechercher'])
            ->getForm();

        $rialForm->handleRequest($request);

        $results = [];
        $topoSearches = [];
        $topoResults = [];
        $totalFiscalCount = 0;

        $isTopoSubmit = $request->request->has('topo_search');
        $submittedBanId = null;

        if ($isTopoSubmit) {
            $topoDataRaw = $request->request->all()['topo_search'];
            $submittedBanId = $topoDataRaw['ban_id'] ?? null;
        }

        if (($rialForm->isSubmitted() && $rialForm->isValid() && !$isTopoSubmit) || ($isTopoSubmit && $submittedBanId)) {
            $banIdsRaw = $isTopoSubmit ? ($request->request->all()['rial_search']['banIds'] ?? '') : $rialForm->get('banIds')->getData();
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
                } catch (\Throwable $e) {
                    $results[] = [
                        'ban_id' => $banId,
                        'identifiant_fiscal' => 'ERROR BAN',
                        'local_data' => '',
                        'is_pseudo_code' => false,
                    ];
                }
            }
        }

        $topoForms = [];
        foreach ($topoSearches as $banId => $topoSearchData) {
            $topoForm = $formFactory->createNamedBuilder('topo_search', FormType::class, $topoSearchData, [
                'action' => $this->generateUrl('back_tools_rial'),
                'method' => 'POST',
                'allow_extra_fields' => true,
            ])
                ->add('code_dep', HiddenType::class)
                ->add('code_commune', HiddenType::class)
                ->add('ban_id', HiddenType::class)
                ->add('libelle', TextType::class, [
                    'label' => 'Libellé de la voie',
                    'required' => true,
                    'attr' => ['placeholder' => 'Ex: LOUBRETTE'],
                    'help' => sprintf(
                        'Pour vous aider à retrouver le libellé exact, vous pouvez consulter la fiche BAN correspondante : <a href="https://adresse.data.gouv.fr/carte-base-adresse-nationale?id=%s" target="_blank" rel="noopener">voir sur adresse.data.gouv.fr</a>',
                        $banId
                    ),
                    'help_html' => true,
                ])
                ->add('submit_topo', SubmitType::class, ['label' => 'Rechercher dans TOPO DGFiP'])
                ->getForm();

            if ($isTopoSubmit && $submittedBanId === $banId) {
                $topoForm->handleRequest($request);
                if ($topoForm->isSubmitted() && $topoForm->isValid()) {
                    $topoData = $topoForm->getData();
                    $topoResults = $topoService->searchVoies(
                        $topoData['code_dep'],
                        $topoData['code_commune'],
                        $topoData['libelle']
                    );
                    $topoSearches[$banId]['libelle'] = $topoData['libelle'];
                    $topoSearches[$banId]['searched'] = true;
                }
            }
            $topoForms[$banId] = $topoForm->createView();
        }

        return $this->render('back/tools/rial.html.twig', [
            'rialForm' => $rialForm->createView(),
            'results' => $results,
            'topoSearches' => $topoSearches,
            'topoForms' => $topoForms,
            'topoResults' => $topoResults,
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
        $isPseudoCode = \count($banParts) >= 2 && 6 === \strlen($banParts[1]);

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
