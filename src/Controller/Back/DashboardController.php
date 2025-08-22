<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Factory\WidgetSettingsFactory;
use App\Form\SearchDashboardAverifierType;
use App\Repository\TerritoryRepository;
use App\Service\DashboardTabPanel\TabDataManager;
use App\Service\ListFilters\SearchDashboardAverifier;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo')]
class DashboardController extends AbstractController
{
    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    #[Route('/', name: 'back_dashboard')]
    public function index(
        Request $request,
        TerritoryRepository $territoryRepository,
        WidgetSettingsFactory $widgetSettingsFactory,
        TabDataManager $tabDataManager,
        #[Autowire(env: 'FEATURE_NEW_DASHBOARD')] ?int $featureNewDashboard = null,
        #[MapQueryParameter('territoireId')] ?int $territoireId = null,
        #[MapQueryParameter('mesDossiersMessagesUsagers')] ?string $mesDossiersMessagesUsagers = null,
        #[MapQueryParameter('mesDossiersAverifier')] ?string $mesDossiersAverifier = null,
    ): Response {
        if ($featureNewDashboard) {
            /** @var User $user */
            $user = $this->getUser();

            if ($user->isUserPartner() && (null === $mesDossiersMessagesUsagers || null === $mesDossiersAverifier)) {
                return $this->redirectToRoute('back_dashboard', [
                    'territoireId' => $territoireId,
                    'mesDossiersMessagesUsagers' => $mesDossiersMessagesUsagers ?? '1',
                    'mesDossiersAverifier' => $mesDossiersAverifier ?? '1',
                ]);
            }

            // Résolution du territoire et des territoires autorisés
            [$territory, $territories] = $this->resolveTerritoryAndTerritories(
                $user,
                $territoryRepository,
                $territoireId
            );

            // Création du formulaire de recherche pour l'onglet "A vérifier"
            $searchDashboardAverifier = new SearchDashboardAverifier($user);
            $formSearchAverifier = $this->createForm(SearchDashboardAverifierType::class, $searchDashboardAverifier, [
                'method' => 'GET',
                'territory' => $territory,
                'mesDossiersAverifier' => $mesDossiersAverifier,
            ]);
            $formSearchAverifier->handleRequest($request);
            
            // Réinitialisation si le formulaire est invalide
            if ($formSearchAverifier->isSubmitted() && !$formSearchAverifier->isValid()) {
                $searchDashboardAverifier = new SearchDashboardAverifier($user);
            }

            return $this->render('back/dashboard/index.html.twig', [
                'territoireSelectedId' => $territoireId,
                'settings' => $widgetSettingsFactory->createInstanceFrom($user, $territory),
                'tab_count_kpi' => $tabDataManager->countDataKpi($territories, $territoireId, $mesDossiersMessagesUsagers, $mesDossiersAverifier),
                'territory' => $territory,
                'mesDossiersMessagesUsagers' => $mesDossiersMessagesUsagers,
                'mesDossiersAverifier' => $mesDossiersAverifier,
                'formSearchAverifier' => $formSearchAverifier,
            ]);
        }

        return $this->render('back/dashboard/index.html.twig');
    }

    /**
     * Résout le territoire sélectionné et la liste des territoires autorisés
     * 
     * @return array{0: Territory|null, 1: array<int, Territory>}
     */
    private function resolveTerritoryAndTerritories(
        User $user,
        TerritoryRepository $territoryRepository,
        ?int $territoireId
    ): array {
        $territories = [];
        $authorizedTerritories = $user->getPartnersTerritories();
        $territory = null;

        // Cas 1: Un territoire spécifique est demandé
        if ($territoireId) {
            // Vérifier si l'utilisateur a accès à ce territoire
            if ($this->isGranted('ROLE_ADMIN') || isset($authorizedTerritories[$territoireId])) {
                $territory = $territoryRepository->find($territoireId);
                if ($territory && $territory->isIsActive()) {
                    $territories[$territory->getId()] = $territory;
                } else {
                    // Le territoire n'existe pas ou n'est pas actif, on le remet à null
                    $territory = null;
                }
            }
        }
        
        // Cas 2: Aucun territoire spécifique ou territoire non autorisé
        if (null === $territory) {
            if ($this->isGranted('ROLE_ADMIN')) {
                // Les admins voient tous les territoires actifs
                $territories = $territoryRepository->findAllList();
            } else {
                // Les autres utilisateurs voient leurs territoires autorisés
                $territories = $authorizedTerritories;
                
                // Pour les responsables territoire, définir le territoire par défaut
                if (!empty($territories)) {
                    $territory = $user->getFirstTerritory();
                }
            }
        }

        return [$territory, $territories];
    }
}
