<?php

namespace App\Manager;

use App\Entity\DesordreCategorie;
use Doctrine\Persistence\ManagerRegistry;

class DesordreCategorieManager extends AbstractManager
{
    public function __construct(protected ManagerRegistry $managerRegistry, string $entityName = DesordreCategorie::class)
    {
        parent::__construct($managerRegistry, $entityName);
    }

    public function createOrUpdate(string $label): DesordreCategorie
    {
        /** @var DesordreCategorie|null $desordreCategorie */
        $desordreCategorie = $this->getRepository()->findOneBy([
            'label' => $label,
        ]);
        if (null === $desordreCategorie) {
            $desordreCategorie = (new DesordreCategorie())
            ->setLabel($label);
        }

        $this->save($desordreCategorie);

        return $desordreCategorie;
    }
}
