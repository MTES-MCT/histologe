<?php

namespace App\Service\BOList;

use App\Dto\BOList\BOHeaderItem;
use App\Dto\BOList\BOListItem;
use App\Dto\BOList\BOListItemLink;
use App\Dto\BOList\BOTable;
use App\Entity\User;
use App\Service\SearchUser;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BOListUser
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function buildTable(Paginator $users, SearchUser $searchUser): BOTable
    {
        return new BOTable(
            headers: $this->getHeaders(),
            data: $this->getData($users),
            tableTitle: count($users).' utilisateur'.(count($users) > 1 ? 's' : '').' trouvé'.(count($users) > 1 ? 's' : ''),
            tableDescription: 'Liste des utilisateurs',
            noDataLabel: 'Aucun utilisateur trouvé',
            rowClass: 'signalement-row',
            paginationSlug: 'back_user_index',
            paginationParams: $this->getPaginationParams($searchUser),
        );
    }

    private function getHeaders(): array
    {
        $list = [];
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $list[] = new BOHeaderItem('Territoire', 'col');
        }
        $list[] = new BOHeaderItem('Utilisateur', 'col');
        $list[] = new BOHeaderItem('E-mail', 'col');
        $list[] = new BOHeaderItem('Partenaire', 'col');
        $list[] = new BOHeaderItem('Type de partenaire', 'col');
        $list[] = new BOHeaderItem('Statut du compte', 'col');
        $list[] = new BOHeaderItem('Dernière connexion', 'col');
        $list[] = new BOHeaderItem('Rôle', 'col');
        if ($this->parameterBag->get('feature_permission_affectation')) {
            $list[] = new BOHeaderItem('Droits d\'affectation', 'col');
        }
        $list[] = new BOHeaderItem('Actions', 'col', 'fr-text--right');

        return $list;
    }

    private function getData(Paginator $users): array
    {
        $list = [];

        /** @var User $user */
        foreach ($users as $user) {
            $item = [];

            if ($this->security->isGranted('ROLE_ADMIN')) {
                $item[] = new BOListItem(label: $user->getTerritory() ? $user->getTerritory()->getZip().' - '.$user->getTerritory()->getName() : 'aucun');
            }

            $item[] = new BOListItem(label: $user->getNomComplet());
            $item[] = new BOListItem(label: $user->getEmail());
            $item[] = new BOListItem(label: $user->getPartner() ? $user->getPartner()->getNom() : 'N/A');
            $item[] = new BOListItem(label: $user->getPartner() && $user->getPartner()->getType() ? $user->getPartner()->getType()->label() : 'N/A');

            if (User::STATUS_INACTIVE === $user->getStatut()) {
                $item[] = new BOListItem(badgeLabels: ['Non activé'], badgeClass: 'fr-badge--error');
            } elseif (User::STATUS_ACTIVE === $user->getStatut()) {
                $item[] = new BOListItem(badgeLabels: ['Activé'], badgeClass: 'fr-badge--success');
            } else {
                $item[] = new BOListItem(badgeLabels: [$user->getStatutLabel()]);
            }

            $item[] = new BOListItem(label: $user->getLastLoginAt() ? $user->getLastLoginAtStr('d/m/Y') : '-');
            $item[] = new BOListItem(label: $user->getRoleLabel());

            if ($this->parameterBag->get('feature_permission_affectation')) {
                $item[] = new BOListItem(label: $user->isSuperAdmin() || $user->isTerritoryAdmin() || $user->hasPermissionAffectation() ? 'Oui' : 'Non');
            }

            $links = [];
            $attr['id'] = 'partner_users_edit_'.$user->getId();
            $attr['aria-controls'] = 'fr-modal-user-edit';
            $attr['data-fr-opened'] = 'false';
            $attr['data-usernom'] = $user->getNom();
            $attr['data-userprenom'] = $user->getPrenom();
            if ($this->parameterBag->get('feature_permission_affectation')) {
                $attr['data-userpermissionaffectation'] = $user->hasPermissionAffectation() ? '1' : '0';
            }
            $attr['data-userismailingactive'] = $user->getIsMailingActive();
            $attr['data-userid'] = $user->getId();
            $attr['data-useremail'] = $user->getEmail();
            $links[] = new BOListItemLink(
                href: '#',
                class: 'fr-btn fr-fi-edit-line fr-mt-3v btn-edit-partner-user',
                attrList: $attr
            );
            $item[] = new BOListItem(
                class: 'fr-text--right',
                links: $links
            );

            $list[] = $item;
        }

        return $list;
    }

    private function getPaginationParams(SearchUser $searchUser): array
    {
        return $searchUser->getUrlParams();
    }
}
