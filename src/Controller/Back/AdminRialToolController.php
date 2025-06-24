<?php

namespace App\Controller\Back;

use App\Service\Gouv\Rial\RialService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/tools/rial', name: 'back_tools_rial')]
#[IsGranted('ROLE_ADMIN')]
class AdminRialToolController extends AbstractController
{
    public function __invoke(Request $request, RialService $rialService)
    {
        $form = $this->createFormBuilder()
            ->add('banIds', TextareaType::class, [
                'label' => 'BAN id(s) (séparés par des virgules ou des retours à la ligne)',
                'required' => true,
            ])
            ->add('submit', SubmitType::class, ['label' => 'Rechercher'])
            ->getForm();

        $form->handleRequest($request);
        $results = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $banIdsRaw = $form->get('banIds')->getData();
            $banIds = array_filter(array_map('trim', preg_split('/[\s,]+/', $banIdsRaw)));
            foreach ($banIds as $banId) {
                try {
                    $identifiantsFiscaux = $rialService->searchLocauxByBanId($banId) ?? [];
                    if (empty($identifiantsFiscaux)) {
                        $results[] = [
                            'ban_id' => $banId,
                            'identifiant_fiscal' => 'Aucun identifiant fiscal pour cet identifiant BAN',
                            'local_data' => '',
                        ];
                        continue;
                    }
                    foreach ($identifiantsFiscaux as $identifiantFiscal) {
                        $localData = $rialService->searchLocalByIdFiscal($identifiantFiscal);
                        if ($localData) {
                            $results[] = [
                                'ban_id' => $banId,
                                'identifiant_fiscal' => $identifiantFiscal,
                                'local_data' => json_encode($localData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                            ];
                        } else {
                            $results[] = [
                                'ban_id' => $banId,
                                'identifiant_fiscal' => $identifiantFiscal,
                                'local_data' => 'Aucune info pour cet identifiant fiscal',
                            ];
                        }
                    }
                } catch (\Throwable $e) {
                    $results[] = [
                        'ban_id' => $banId,
                        'identifiant_fiscal' => 'ERROR BAN',
                        'local_data' => '',
                    ];
                }
            }
        }

        return $this->render('back/tools/rial.html.twig', [
            'form' => $form->createView(),
            'results' => $results,
        ]);
    }
} 