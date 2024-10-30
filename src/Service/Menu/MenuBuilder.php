<?php

namespace App\Service\Menu;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class MenuBuilder
{
    public function __construct(
        private RequestStack $requestStack,
        private ParameterBagInterface $parameterBag,
        private Security $currentRoute
    ) {
    }

    public function build(): MenuItem
    {
        /** @var User $user */
        $user = $this->currentRoute->getUser();
        $signalementsSubMenu = (new MenuItem(label: 'Signalements', expanded: $this->isExpanded(['back_index', 'back_signalement']), roleGranted: User::ROLE_USER))
            ->addChild(new MenuItem(label: 'Liste', route: 'back_index', roleGranted: User::ROLE_USER))
            ->addChild(new MenuItem(label: 'Créer un signalement', route: 'front_signalement', roleGranted: User::ROLE_USER));

        $donneesChiffreesSubMenu = (new MenuItem(label: 'Données chiffrées', expanded: $this->isExpanded(['back_cartographie', 'back_statistiques']), roleGranted: User::ROLE_USER))
            ->addChild(new MenuItem(label: 'Cartographie', route: 'back_cartographie', roleGranted: User::ROLE_USER))
            ->addChild(new MenuItem(label: 'Statistiques', route: 'back_statistiques', roleGranted: User::ROLE_USER));

        $adminToolsSubItem = (new MenuItem(
            label: 'Outils Admin',
            expanded: $this->isExpanded(['back_user', 'back_tags', 'back_partner', 'back_zone_index']),
            roleGranted: User::ROLE_ADMIN_PARTNER, )
        )
            ->addChild(new MenuItem(label: 'Partenaires', route: 'back_partner_index', roleGranted: User::ROLE_ADMIN_TERRITORY))
            ->addChild(new MenuItem(label: 'Mon partenaire', route: 'back_partner_view', routeParameters: ['id' => $user->getPartner()?->getId()], roleGranted: User::ROLE_ADMIN_PARTNER, roleNotGranted: User::ROLE_ADMIN_TERRITORY))
            ->addChild(new MenuItem(label: 'Utilisateurs', route: 'back_user_index', roleGranted: User::ROLE_ADMIN_TERRITORY, featureEnable: (bool) $this->parameterBag->get('feature_export_users')))
            ->addChild(new MenuItem(label: 'Etiquettes', route: 'back_tags_index', roleGranted: User::ROLE_ADMIN_TERRITORY))
            ->addChild(new MenuItem(label: 'Zones', route: 'back_zone_index', roleGranted: User::ROLE_ADMIN_TERRITORY, featureEnable: (bool) $this->parameterBag->get('feature_zonage')));

        $superAdminToolsSubItem = (new MenuItem(
            label: 'Outils SA',
            expanded: $this->isExpanded(['back_archived', 'back_account_index', 'back_auto_affectation', 'back_territory_index']),
            roleGranted: User::ROLE_ADMIN)
        )
            ->addChild(new MenuItem(label: 'Partenaires archivés', route: 'back_archived_partner_index', roleGranted: User::ROLE_ADMIN))
            ->addChild(new MenuItem(label: 'Comptes archivés', route: 'back_account_index', roleGranted: User::ROLE_ADMIN))
            ->addChild(new MenuItem(label: 'Signalement archivés', route: 'back_archived_signalements_index', roleGranted: User::ROLE_ADMIN))
            ->addChild(new MenuItem(label: 'Règles d\'auto-affectation', route: 'back_auto_affectation_rule_index', roleGranted: User::ROLE_ADMIN))
            ->addChild(new MenuItem(label: 'Territoires', route: 'back_territory_index', roleGranted: User::ROLE_ADMIN, featureEnable: (bool) $this->parameterBag->get('feature_grille_visite')));

        return (new MenuItem(label: 'root', route: ''))
            ->addChild(new MenuItem(label: 'Tableau de bord', route: 'back_dashboard', icon: 'fr-icon-home-4-fill', roleGranted: User::ROLE_USER))
            ->addChild($signalementsSubMenu)
            ->addChild($donneesChiffreesSubMenu)
            ->addChild($adminToolsSubItem)
            ->addChild($superAdminToolsSubItem);
    }

    private function isExpanded(array $routes): bool
    {
        foreach ($routes as $route) {
            if (str_starts_with($this->requestStack->getCurrentRequest()->get('_route'), $route)) {
                return true;
            }
        }

        return false;
    }
}
