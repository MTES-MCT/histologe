<?php

namespace App\Controller\Back;

use App\Entity\Tag;
use App\Entity\User;
use App\Repository\SignalementRepository;
use App\Repository\TagRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/statistiques')]
class BackStatistiquesController extends AbstractController
{
    /**
     * Route to access Statistiques in the back-office.
     */
    #[Route('/', name: 'back_statistiques')]
    public function index(): Response
    {
        if ($this->getUser()) {
            $title = 'Statistiques';

            return $this->render('back/statistiques/index.html.twig', [
                'title' => $title,
            ]);
        }

        return $this->redirectToRoute('home');
    }

    /**
     * Route called by Ajax requests (filters filtered by user type, statistics filtered by filters).
     */
    #[Route('/filter', name: 'back_statistiques_filter')]
    public function filter(Request $request, TagRepository $tagsRepository, SignalementRepository $signalementRepository): Response
    {
        if ($this->getUser()) {
            $buffer = [];

            /**
             * @var User $user
             */
            $user = $this->getUser();
            $territory = $user->getTerritory();

            // List of the Communnes linked to a User
            // - if user/admin of Territoire: only Communes from a Territoire (in the BAN)
            // - if super admin: every Communes
            $buffer['list_communes'] = [];

            // List of the Etiquettes linked to a User
            // - if user/admin of Territoire: only Etiquettes from a Territoire
            // - if super admin: every Etiquettes of the platform
            $tagList = $tagsRepository->findAllActive($territory);
            /**
             * @var Tag $tagItem
             */
            foreach ($tagList as $tagItem) {
                $buffer['list_etiquettes'][$tagItem->getId()] = $tagItem->getLabel();
            }

            $communes = $request->get('communes');
            $statut = $request->get('statut');
            $etiquettes = $request->get('etiquettes');
            $type = $request->get('type');
            $dateStart = $request->get('dateStart');
            $dateEnd = $request->get('dateEnd');
            $countRefused = $request->get('countRefused');
            $hasCountRefused = '1' == $countRefused;

            $result = $signalementRepository->findByFilters($statut, $hasCountRefused);

            $totalCriticite = 0;
            $countHasDaysValidation = 0;
            $totalDaysValidation = 0;
            $countHasDaysClosure = 0;
            $totalDaysClosure = 0;
            /**
             * @var Signalement $signalementItem
             */
            foreach ($result as $signalementItem) {
                $totalCriticite += $signalementItem->getScoreCreation();
                $dateCreatedAt = $signalementItem->getCreatedAt();
                if (null !== $dateCreatedAt) {
                    $dateValidatedAt = $signalementItem->getValidatedAt();
                    if (null !== $dateValidatedAt) {
                        ++$countHasDaysValidation;
                        $dateDiff = $dateCreatedAt->diff($dateValidatedAt);
                        $totalDaysValidation += $dateDiff->d;
                    }
                    $dateClosedAt = $signalementItem->getClosedAt();
                    if (null !== $dateClosedAt) {
                        ++$countHasDaysClosure;
                        $dateDiff = $dateCreatedAt->diff($dateClosedAt);
                        $totalDaysClosure += $dateDiff->d;
                    }
                }
            }

            $countSignalement = \count($result);
            $averageCriticite = $countSignalement > 0 ? round($totalCriticite / $countSignalement) : '-';
            $averageDaysValidation = $countHasDaysValidation > 0 ? round($totalDaysValidation * 10 / $countHasDaysValidation) / 10 : '-';
            $averageDaysClosure = $countHasDaysClosure > 0 ? round($totalDaysClosure * 10 / $countHasDaysClosure) / 10 : '-';

            $buffer['count_signalement'] = $countSignalement;
            $buffer['average_criticite'] = $averageCriticite;
            $buffer['average_days_validation'] = $averageDaysValidation;
            $buffer['average_days_closure'] = $averageDaysClosure;

            $buffer['countSignalementPerMonth'] = [];
            $buffer['countSignalementPerPartenaire'] = [];
            $buffer['countSignalementPerSituation'] = [];
            $buffer['countSignalementPerCriticite'] = [];
            $buffer['countSignalementPerStatut'] = [];
            $buffer['countSignalementPerCriticitePercent'] = [];
            $buffer['countSignalementPerVisite'] = [];

            $buffer['response'] = 'success';

            return $this->json($buffer);
        }

        return $this->json(['response' => 'error'], 400);
    }
}
