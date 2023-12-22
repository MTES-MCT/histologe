<?php

namespace App\Manager;

use App\Entity\DesordrePrecision;
use App\Entity\Enum\Qualification;
use Doctrine\Persistence\ManagerRegistry;

class DesordrePrecisionManager extends AbstractManager
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
        string $entityName = DesordrePrecision::class
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    /**
     * @param array $data The array representing the DesordrePrecision.
     *                    - 'label' (string): The title of the DesordrePrecision.
     *                    - 'desordreCritere' (DesordreCritere): DesordreCritere linked
     *                    - 'coef' (string): The coef of the DesordrePrecision
     *                    - 'danger' (string): 'Oui' if is_danger.
     *                    - 'suroccupation' (string): 'Oui' if is_suroccupation.
     *                    - 'procedure' (string): The List of Qualification .
     */
    public function createOrUpdate(string $slug, array $data): DesordrePrecision
    {
        /** @var DesordrePrecision|null $desordrePrecision */
        $desordrePrecision = $this->getRepository()->findOneBy([
            'desordrePrecisionSlug' => $slug,
        ]);
        if (null === $desordrePrecision) {
            $desordrePrecision = (new DesordrePrecision());
        }

        $coef = str_replace(',', '.', $data['coef'] ?? 0);

        $qualification = [];
        if (isset($data['procedure'])) {
            $procedure = explode(',', $data['procedure']);
            foreach ($procedure as $qualificationLabel) {
                $qualification[] = Qualification::tryFromLabel($qualificationLabel);
            }
        }

        $desordrePrecision->setLabel($data['label'])
        ->setDesordreCritere($data['desordreCritere'])
        ->setCoef((float) $coef)
        ->setIsDanger('Oui' === $data['danger'])
        ->setIsSuroccupation('Oui' === $data['suroccupation'])
        ->setDesordrePrecisionSlug($slug)
        ->setQualification($qualification);

        $this->save($desordrePrecision);

        return $desordrePrecision;
    }
}
