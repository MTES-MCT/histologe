<?php

namespace App\Manager;

use App\Entity\DesordreCritere;
use App\Entity\Enum\DesordreCritereZone;
use Doctrine\Persistence\ManagerRegistry;

class DesordreCritereManager extends AbstractManager
{
    public function __construct(protected ManagerRegistry $managerRegistry, string $entityName = DesordreCritere::class)
    {
        parent::__construct($managerRegistry, $entityName);
    }

    /**
     * @param array $data The array representing the DesordreCritere.
     *                    - 'slugCategorie' (string): The "front slug" value of the Categorie.
     *                    - 'labelCategorie' (string): The title of the Categorie.
     *                    - 'zoneCategorie' (string): The zone of the Categorie/Critere.
     *                    - 'labelCritere' (string): The title of the Critere.
     *                    - 'desordreCategorie' (DesordreCategorie): DesordreCategorie
     */
    public function createOrUpdate(string $slug, array $data): DesordreCritere
    {
        /** @var DesordreCritere|null $desordreCritere */
        $desordreCritere = $this->getRepository()->findOneBy([
            'slugCritere' => $slug,
        ]);
        if (null === $desordreCritere) {
            $desordreCritere = (new DesordreCritere());
        }

        $desordreCritere->setSlugCategorie($data['slugCategorie'])
        ->setLabelCategorie($data['labelCategorie'])
        ->setZoneCategorie(DesordreCritereZone::tryFromLabel($data['zoneCategorie']))
        ->setLabelCritere($data['labelCritere'])
        ->setDesordreCategorie($data['desordreCategorie'])
        ->setSlugCritere($slug);

        $this->save($desordreCritere);

        return $desordreCritere;
    }

    public function getCriteresSlugsByCategorie(string $categoryDisorderSlug): array
    {
        $criteres = $this->getRepository()->findBy(['slugCategorie' => $categoryDisorderSlug]);

        return array_map(fn ($critere) => $critere->getSlugCritere(), $criteres);
    }

    public function getCriteresSlugsInDraft(array $filteredDataOfDraft, array $availableCritereSlugs): array
    {
        $criteresSlugs = array_filter($filteredDataOfDraft, function ($value, $slug) use ($availableCritereSlugs) {
            if (\in_array($slug, $availableCritereSlugs)) {
                if (1 === $value) {
                    return true;
                }
            }

            return false;
        }, \ARRAY_FILTER_USE_BOTH);

        // cas particulier pour desordres_logement_chauffage_type_aucun
        if (isset($filteredDataOfDraft['desordres_logement_chauffage_type'])
            && 'aucun' === $filteredDataOfDraft['desordres_logement_chauffage_type']) {
            $criteresSlugs['desordres_logement_chauffage_type_aucun'] = 1;
        }

        return $criteresSlugs;
    }
}
