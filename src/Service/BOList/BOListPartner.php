<?php

namespace App\Service\BOList;

use App\Dto\BOList\BOHeaderItem;
use App\Dto\BOList\BOListItem;
use App\Dto\BOList\BOListItemLink;
use App\Dto\BOList\BOTable;
use App\Entity\Partner;
use App\Entity\Territory;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BOListPartner
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function buildTable(Paginator $partners, ?Territory $currentTerritory, ?string $currentType, ?string $userTerms): BOTable
    {
        return new BOTable(
            headers: $this->getHeaders(),
            data: $this->getData($partners),
            noDataLabel: 'Aucun partenaire trouvé',
            rowClass: 'partner-row',
            paginationSlug: 'back_partner_index',
            paginationParams: $this->getPaginationParams($currentTerritory, $currentType, $userTerms),
        );
    }

    private function getHeaders(): array
    {
        $list = [];
        $list[] = new BOHeaderItem('Id', 'col', 'number');
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $list[] = new BOHeaderItem('Territoire', 'col');
        }
        $list[] = new BOHeaderItem('Nom', 'col');
        $list[] = new BOHeaderItem('Type', 'col');
        $list[] = new BOHeaderItem('Compétences', 'col');
        $list[] = new BOHeaderItem('Codes INSEE', 'col');
        if ($this->parameterBag->get('feature_zonage')) {
            $list[] = new BOHeaderItem('Zones', 'col');
        }
        $list[] = new BOHeaderItem('Actions', 'col', 'fr-text--right');

        return $list;
    }

    private function getData(Paginator $partners): array
    {
        $list = [];

        /** @var Partner $partner */
        foreach ($partners as $partner) {
            $item = [];

            $item[] = new BOListItem(label: $partner->getId());

            if ($this->security->isGranted('ROLE_ADMIN')) {
                $item[] = new BOListItem(label: $partner->getTerritory() ? $partner->getTerritory()->getZip().' - '.$partner->getTerritory()->getName() : 'aucun');
            }

            $item[] = new BOListItem(label: $partner->getNom());
            $item[] = new BOListItem(label: $partner->getType() ? $partner->getType()->label() : ($partner->getIsCommune() ? 'Commune' : 'N/A'));

            if (count($partner->getCompetence())) {
                $item[] = new BOListItem(badgeLabels: [count($partner->getCompetence())]);
            } else {
                $item[] = new BOListItem(label: '/');
            }

            if (count($partner->getInsee())) {
                $badgeLabels = [];
                foreach ($partner->getInsee() as $insee) {
                    $badgeLabels[] = $insee;
                    if (count($badgeLabels) >= 4) {
                        break;
                    }
                }
                $label = count($partner->getInsee()) > 4 ? '+ '.(count($partner->getInsee()) - 4) : null;
                $item[] = new BOListItem(badgeLabels: $badgeLabels, label: $label);
            } else {
                $item[] = new BOListItem(label: '/');
            }

            if ($this->parameterBag->get('feature_zonage')) {
                if (count($partner->getZones())) {
                    $badgeLabels = [];
                    foreach ($partner->getZones() as $zone) {
                        $badgeLabels[] = $zone->getName();
                        if (count($badgeLabels) >= 4) {
                            break;
                        }
                    }
                    $label = count($partner->getZones()) > 4 ? '+ '.(count($partner->getZones()) - 4) : null;
                    $item[] = new BOListItem(badgeLabels: $badgeLabels, label: $label);
                } else {
                    $item[] = new BOListItem(label: '/');
                }
            }

            $links = [];
            $links[] = new BOListItemLink(
                href: $this->urlGenerator->generate('back_partner_view', [
                    'id' => $partner->getId(),
                ]),
                class: 'fr-btn fr-fi-arrow-right-line fr-btn--sm'
            );
            $attr['id'] = 'partners_delete_'.$partner->getId();
            $attr['aria-controls'] = 'fr-modal-partner-delete';
            $attr['data-fr-opened'] = 'false';
            $attr['data-partnername'] = $partner->getNom();
            $attr['data-partnerid'] = $partner->getId();
            $links[] = new BOListItemLink(
                href: '#',
                class: 'fr-btn fr-btn--danger fr-fi-delete-line fr-btn--sm btn-delete-partner',
                attrList: $attr
            );
            $item[] = new BOListItem(
                class: 'fr-text--right fr-ws-nowrap',
                links: $links
            );

            $list[] = $item;
        }

        return $list;
    }

    private function getPaginationParams(?Territory $currentTerritory, ?string $currentType, ?string $userTerms): array
    {
        return [
            'territory' => $currentTerritory ? $currentTerritory->getId() : null,
            'type' => $currentType ?? null,
            'userTerms' => $userTerms,
        ];
    }
}
