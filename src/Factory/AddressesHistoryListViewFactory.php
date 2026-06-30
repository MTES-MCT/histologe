<?php

namespace App\Factory;

use App\Dto\AddressesHistoryListView;
use App\Dto\AddressesHistorySignalementView;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AddressesHistoryListViewFactory
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
    ): AddressesHistoryListView {
        return new AddressesHistoryListView(
            address: $addressOccupant,
            cp: $cpOccupant,
            ville: $villeOccupant,
            territoryId: $territoryId,
            addressForHuman: $addressForHuman,
            communeForHuman: $communeForHuman,
        );
    }

    /**
     * @param array<mixed> $data
     */
    public function createSignalementInstanceFromSignalementData(array $data): AddressesHistorySignalementView
    {
        $url = $this->urlGenerator->generate('back_signalement_view', [
            'uuid' => $data['uuid'],
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return new AddressesHistorySignalementView(
            url: $url,
            ref: $data['reference'],
            usager: $data['prenomOccupant'].' '.$data['nomOccupant'],
            statut: $data['statut']->label(),
        );
    }
}
