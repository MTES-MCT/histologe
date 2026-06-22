<?php

namespace App\Factory;

use App\Dto\HistoAddressListView;
use App\Dto\HistoAddressSignalementView;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HistoAddressListViewFactory
{
    public function __construct(
        protected UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function createInstance(
        string $addressOccupant,
        string $cpOccupant,
        string $villeOccupant,
        int $territoryId,
        string $addressForHuman,
        string $communeForHuman,
    ): HistoAddressListView {
        return new HistoAddressListView(
            address: $addressOccupant,
            cp: $cpOccupant,
            ville: $villeOccupant,
            territoryId: $territoryId,
            addressForHuman: $addressForHuman,
            communeForHuman: $communeForHuman,
        );
    }

    public function createSignalementInstanceFromSignalementData(array $data): HistoAddressSignalementView
    {
        $url = $this->urlGenerator->generate('back_signalement_view', [
            'uuid' => $data['uuid'],
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return new HistoAddressSignalementView(
            url: $url,
            ref: $data['reference'],
            usager: $data['prenomOccupant'] . ' ' . $data['nomOccupant'],
            statut: $data['statut']->label(),
        );
    }
}
