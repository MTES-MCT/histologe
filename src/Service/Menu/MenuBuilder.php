<?php

namespace App\Service\Menu;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class MenuBuilder
{
    public function __construct(
        private readonly Security $currentRoute,
        #[Autowire(env: 'FEATURE_NEW_DASHBOARD')]
        private readonly bool $featureNewDashboard,
        #[Autowire(env: 'FEATURE_NEW_DOCUMENT_SPACE')]
        private readonly bool $featureNewDocumentSpace,
    ) {
    }

    public function build(): MenuItem
    {
        /** @var User $user */
        $user = $this->currentRoute->getUser();
        $listRouteParameters = [];
        if ($this->currentRoute->isGranted(User::ROLE_ADMIN)) {
            $listRouteParameters = ['status' => 'nouveau', 'isImported' => 'oui'];
        } elseif ($this->featureNewDashboard && $user->isUserPartner()) {
            $listRouteParameters = ['showMySignalementsOnly' => 'oui'];
        }
        $signalementsSubMenu = (new MenuItem(label: 'Signalements', roleGranted: User::ROLE_USER))
            ->addChild(new MenuItem(label: 'Liste', route: 'back_signalements_index', routeParameters: $listRouteParameters, roleGranted: User::ROLE_USER));
        $signalementsSubMenu
            ->addChild(new MenuItem(label: 'Mes brouillons', route: 'back_signalement_drafts', roleGranted: User::ROLE_USER));
        $signalementsSubMenu->addChild(new MenuItem(label: 'Créer un signalement', route: 'back_signalement_create', roleGranted: User::ROLE_USER))
            ->addChild(new MenuItem(route: 'back_signalement_view'))
        ;

        $donneesChiffreesSubMenu = (new MenuItem(label: 'Données chiffrées', roleGranted: User::ROLE_USER))
            ->addChild(new MenuItem(label: 'Cartographie', route: 'back_cartographie', roleGranted: User::ROLE_USER))
            ->addChild(new MenuItem(label: 'Statistiques', route: 'back_statistiques', roleGranted: User::ROLE_USER))
        ;

        $adminToolsSubItem = (new MenuItem(label: 'Outils Admin', roleGranted: User::ROLE_ADMIN_PARTNER))
            ->addChild(new MenuItem(label: 'Partenaires', route: 'back_partner_index', roleGranted: User::ROLE_ADMIN_TERRITORY));
        foreach ($user->getPartners() as $partner) {
            $partnerName = $user->getPartners()->count() > 1 ? ' '.$partner->getNom() : '';
            $adminToolsSubItem->addChild(new MenuItem(label: 'Mon partenaire'.$partnerName, route: 'back_partner_view', routeParameters: ['id' => $partner->getId()], roleGranted: User::ROLE_ADMIN_PARTNER, roleNotGranted: User::ROLE_ADMIN_TERRITORY));
        }
        $adminToolsSubItem->addChild(new MenuItem(label: 'Utilisateurs', route: 'back_user_index', roleGranted: User::ROLE_ADMIN_TERRITORY));
        if ($this->featureNewDocumentSpace) {
            $adminToolsSubItem->addChild(new MenuItem(label: $user->isSuperAdmin() ? 'Gérer les territoires' : 'Gérer mon territoire', route: 'back_territory_management_index', roleGranted: User::ROLE_ADMIN_TERRITORY));
        } else {
            $adminToolsSubItem->addChild(new MenuItem(label: 'Etiquettes', route: 'back_tags_index', roleGranted: User::ROLE_ADMIN_TERRITORY))
                ->addChild(new MenuItem(label: 'Zones', route: 'back_zone_index', roleGranted: User::ROLE_ADMIN_TERRITORY));
        }
        $adminToolsSubItem->addChild(new MenuItem(route: 'back_partner_new'))
            ->addChild(new MenuItem(route: 'back_partner_edit'))
            ->addChild(new MenuItem(route: 'back_partner_edit_perimetre'))
            ->addChild(new MenuItem(route: 'back_zone_show'))
            ->addChild(new MenuItem(route: 'back_zone_edit'))
        ;

        $territoryFilesSubMenu = null;
        if ($this->featureNewDocumentSpace && !$user->isTerritoryAdmin() && !$user->isSuperAdmin()) {
            $territoryFilesSubMenu = (new MenuItem(label: 'Espace documentaire', route: 'back_territory_files_index', roleGranted: User::ROLE_USER));
        }

        $superAdminToolsSubItem = (new MenuItem(label: 'Outils SA', roleGranted: User::ROLE_ADMIN))
            ->addChild(new MenuItem(label: 'Partenaires archivés', route: 'back_archived_partner_index', roleGranted: User::ROLE_ADMIN))
            ->addChild(new MenuItem(label: 'Comptes archivés', route: 'back_archived_users_index', roleGranted: User::ROLE_ADMIN))
            ->addChild(new MenuItem(label: 'Signalement archivés', route: 'back_archived_signalements_index', roleGranted: User::ROLE_ADMIN))
            ->addChild(new MenuItem(label: 'Règles d\'auto-affectation', route: 'back_auto_affectation_rule_index', roleGranted: User::ROLE_ADMIN))
            ->addChild(new MenuItem(label: 'Résumés de suivis', route: 'back_suivi_summaries_index', roleGranted: User::ROLE_ADMIN))
            ->addChild(new MenuItem(label: 'Territoires', route: 'back_territory_index', roleGranted: User::ROLE_ADMIN))
            ->addChild(new MenuItem(label: 'Bailleurs', route: 'back_bailleur_index', roleGranted: User::ROLE_ADMIN))
            ->addChild(new MenuItem(label: 'Outil RIAL par BAN ID', route: 'back_tools_rial', roleGranted: User::ROLE_ADMIN))
            ->addChild(new MenuItem(label: 'Connexions SI externes', route: 'back_interconnexion_index', roleGranted: User::ROLE_ADMIN));
        if ($this->featureNewDashboard) {
            $superAdminToolsSubItem
                ->addChild(new MenuItem(label: 'Affectations sans prise en charge', route: 'back_affectation_without_subscription_index', roleGranted: User::ROLE_ADMIN));
        }
        $superAdminToolsSubItem
            ->addChild(new MenuItem(route: 'back_archived_users_reactiver'))
            ->addChild(new MenuItem(route: 'back_territory_edit'))
            ->addChild(new MenuItem(route: 'back_bailleur_edit'))
            ->addChild(new MenuItem(route: 'back_auto_affectation_rule_new'))
            ->addChild(new MenuItem(route: 'back_auto_affectation_rule_edit'))
        ;

        $listRouteBOParameters = [];
        if (!$this->currentRoute->isGranted(User::ROLE_ADMIN_PARTNER)) {
            $listRouteBOParameters = ['mesDossiersMessagesUsagers' => '1', 'mesDossiersAverifier' => '1'];
        }

        $menu = (new MenuItem(label: 'root', route: ''))
            ->addChild(new MenuItem(label: 'Tableau de bord', route: 'back_dashboard', icon: 'fr-icon-home-4-fill', roleGranted: User::ROLE_USER, routeParameters: $listRouteBOParameters))
            ->addChild($signalementsSubMenu)
            ->addChild($donneesChiffreesSubMenu)
            ->addChild($adminToolsSubItem)
        ;
        if (null !== $territoryFilesSubMenu) {
            $menu->addChild($territoryFilesSubMenu);
        }
        $menu->addChild($superAdminToolsSubItem);

        return $menu;
    }
}
