<?php

namespace App\Controller\Back;

use App\Entity\Tag;
use App\Entity\User;
use App\Repository\TagRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/statistiques')]
class BackStatistiquesController extends AbstractController
{
    /**
     * Route d'accès à la page Statistiques du BO.
     */
    #[Route('/', name: 'back_statistiques')]
    public function index(): Response
    {
        $title = 'Statistiques';

        return $this->render('back/statistiques/index.html.twig', [
            'title' => $title,
        ]);
    }

    /**
     * Route pour les requêtes Ajax de récupération des statistiques filtrées.
     */
    #[Route('/filter', name: 'back_statistiques_filter')]
    public function filter(TagRepository $tagsRepository): Response
    {
        if ($this->getUser()) {
            $buffer = [];

            /**
             * @var User $user
            */
            $user = $this->getUser();
            $territory = $user->getTerritory();

            // Liste des communes liées à cet utilisateur
            // Cas possibles :
            // - utilisateur/admin territoire : toutes les communes du département via la BAN
            // - super admin : ??
            $buffer['list_communes'] = [];
            // Liste des étiquettes liées à cet utilisateur
            // - utilisateur/admin territoire : les étiquettes liées au territoire
            // - super admin : toutes les étiquettes de la plateforme
            $tagList = $tagsRepository->findAllActive($territory);
            /**
             * @var Tag $tagItem
             */
            foreach ($tagList as $tagItem) {
                $buffer['list_etiquettes'][$tagItem->getId()] = $tagItem->getLabel();
            }

            $buffer['count_signalement'] = 1;
            $buffer['average_criticite'] = 1;
            $buffer['average_days_validation'] = 1;
            $buffer['average_days_closure'] = 1;

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
