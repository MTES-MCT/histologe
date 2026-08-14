<?php

namespace App\Controller\Back;

use App\Service\Gouv\Rial\RialService;
use App\Service\Gouv\Topo\TopoService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/tools/rial')]
#[IsGranted('ROLE_ADMIN')]
class RialToolController extends AbstractController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route('/', name: 'back_tools_rial', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        RialService $rialService,
        TopoService $topoService,
        #[Autowire(env: 'RIAL_ENABLE')]
        string $rialEnable,
    ): Response {
        if (!$rialEnable) {
            return $this->render('back/tools/rial.html.twig', ['rial_enable' => false]);
        }
        $rialData = ['banIds' => $request->request->all()['rial_search']['banIds'] ?? null];
        $form = $this->container->get('form.factory')->createNamedBuilder('rial_search', FormType::class, $rialData, [
            'allow_extra_fields' => true,
        ])
            ->add('banIds', TextareaType::class, [
                'label' => 'BAN id(s) (séparés par des virgules ou des retours à la ligne)',
                'required' => true,
            ])
            ->add('submit', SubmitType::class, ['label' => 'Rechercher'])
            ->getForm();

        $form->handleRequest($request);
        $results = [];
        $topoSearchData = null;
        $topoResults = [];
        $banIdsRaw = null;

        $isTopoSubmit = $request->request->has('topo_search');

        if ($isTopoSubmit) {
            $topoDataRaw = $request->request->all()['topo_search'];
            $topoSearchData = [
                'ban_id' => $topoDataRaw['ban_id'] ?? '',
                'code_dep' => $topoDataRaw['code_dep'] ?? '',
                'code_commune' => $topoDataRaw['code_commune'] ?? '',
            ];
            // On conserve les identifiants BAN saisis dans le formulaire RIAL
            $rialDataRaw = $request->request->all()['rial_search'] ?? [];
            $banIdsRaw = $rialDataRaw['banIds'] ?? null;
        }

        if ($form->isSubmitted() && !$isTopoSubmit) {
            if ($form->isValid()) {
                $banIdsRaw = $form->get('banIds')->getData();
                $parts = preg_split('/[\s,]+/', $banIdsRaw);
                $banIds = array_filter(array_map('trim', $parts ?: []));
                foreach ($banIds as $banId) {
                    try {
                        $identifiantsFiscaux = $rialService->searchLocauxByBanId($banId) ?? [];
                        $isPseudoCode = false;
                        $banParts = explode('_', $banId);
                        if (\count($banParts) >= 2 && 6 === \strlen($banParts[1])) {
                            $isPseudoCode = true;
                            $insee = $banParts[0];
                            $codeDep = $this->extractDepartement($insee);
                            $codeCommune = $this->extractCommune($insee, $codeDep);
                            $topoSearchData = [
                                'ban_id' => $banId,
                                'code_dep' => $codeDep,
                                'code_commune' => $codeCommune,
                            ];
                        }

                        if (empty($identifiantsFiscaux)) {
                            $results[] = [
                                'ban_id' => $banId,
                                'identifiant_fiscal' => 'Aucun identifiant fiscal pour cet identifiant BAN',
                                'local_data' => '',
                                'is_pseudo_code' => $isPseudoCode,
                            ];
                            continue;
                        }
                        foreach ($identifiantsFiscaux as $identifiantFiscal) {
                            $localData = $rialService->searchLocalByIdFiscal($identifiantFiscal);
                            if ($localData) {
                                $results[] = [
                                    'ban_id' => $banId,
                                    'identifiant_fiscal' => $identifiantFiscal,
                                    'local_data' => json_encode($localData, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE),
                                    'is_pseudo_code' => $isPseudoCode,
                                ];
                            } else {
                                $results[] = [
                                    'ban_id' => $banId,
                                    'identifiant_fiscal' => $identifiantFiscal,
                                    'local_data' => 'Aucune info pour cet identifiant fiscal',
                                    'is_pseudo_code' => $isPseudoCode,
                                ];
                            }
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
        } elseif ($isTopoSubmit && isset($topoSearchData['ban_id'])) {
            // If it's a topo search, we still want to show the RIAL "no result" line 
            // to trigger the display of the TOPO form in the template
            $results[] = [
                'ban_id' => $topoSearchData['ban_id'],
                'identifiant_fiscal' => 'Aucun identifiant fiscal pour cet identifiant BAN',
                'local_data' => '',
                'is_pseudo_code' => true,
            ];
        }

        $topoForm = null;
        // On recrée les données topoSearchData si on est en POST sur le formulaire TOPO
        // pour pouvoir reconstruire le formulaire et le valider
        if ($topoSearchData) {
            $topoForm = $this->container->get('form.factory')->createNamedBuilder('topo_search', FormType::class, $topoSearchData, [
                'action' => $this->generateUrl('back_tools_rial'),
                'method' => 'POST',
                'attr' => ['id' => 'topo-search-form'],
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
                        $topoSearchData['ban_id']
                    ),
                    'help_html' => true,
                ])
                ->add('submit_topo', SubmitType::class, ['label' => 'Rechercher dans TOPO DGFiP'])
                ->getForm();

            if ($isTopoSubmit) {
                $topoForm->handleRequest($request);
                if ($topoForm->isSubmitted() && $topoForm->isValid()) {
                    $topoData = $topoForm->getData();
                    $topoResults = $topoService->searchVoies(
                        $topoData['code_dep'],
                        $topoData['code_commune'],
                        $topoData['libelle']
                    );
                    $topoSearchData['libelle'] = $topoData['libelle'];
                    $topoSearchData['searched'] = true;
                }
            }
        }

        return $this->render('back/tools/rial.html.twig', [
            'rial_enable' => true,
            'form' => $form->createView(),
            'results' => $results,
            'topoSearchData' => $topoSearchData,
            'topoForm' => $topoForm?->createView(),
            'topoResults' => $topoResults,
        ]);
    }

    private function extractDepartement(string $insee): string
    {
        if (str_starts_with($insee, '97')) {
            return substr($insee, 0, 3);
        }

        return substr($insee, 0, 2);
    }

    private function extractCommune(string $insee, string $codeDep): string
    {
        return substr($insee, \strlen($codeDep));
    }
}
