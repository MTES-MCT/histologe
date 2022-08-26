<?php

namespace App\Controller\Back;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/statistiques')]
class BackStatistiquesController extends AbstractController
{
    /**
     * Route d'accès à la page Statistiques du BO
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
     * Route pour les requêtes Ajax de récupération des statistiques filtrées
     */
    #[Route('/filter', name: 'back_statistiques_filter')]
    public function filter(): Response
    {
        if ($this->getUser()) {
            $buffer = [];

            // Liste des communes liées à cet utilisateur
            // Cas possibles :
            // - utilisateur/admin territoire : toutes les communes du département ? seulement celles où il y a des signalements ?
            // - super admin : ??
            $buffer['list_communes'] = [];
            // Liste des étiquettes liées à cet utilisateur
            // Cas possibles :
            // - utilisateur/admin territoire : les étiquettes liées au territoire
            // - super admin : ??
            $buffer['list_etiquettes'] = [];

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



            return $this->json(['response' => 'success']);
        }
        return $this->json(['response' => 'error'], 400);
    }
}