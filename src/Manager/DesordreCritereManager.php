<?php

namespace App\Manager;

use App\Entity\DesordreCritere;
use Doctrine\Persistence\ManagerRegistry;

class DesordreCritereManager extends AbstractManager
{
    public function __construct(protected ManagerRegistry $managerRegistry, string $entityName = DesordreCritere::class)
    {
        parent::__construct($managerRegistry, $entityName);
    }

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
        ->setZoneCategorie($data['zoneCategorie'])
        ->setLabelCritere($data['labelCritere'])
        ->setDesordreCategorie($data['desordreCategorie'])
        ->setSlugCritere($slug);

        $this->save($desordreCritere);

        return $desordreCritere;
    }
}
