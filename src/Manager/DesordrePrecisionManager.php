<?php

namespace App\Manager;

use App\Entity\DesordrePrecision;
use App\Entity\Enum\Qualification;
use Doctrine\Persistence\ManagerRegistry;

class DesordrePrecisionManager extends AbstractManager
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
        string $entityName = DesordrePrecision::class,
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
     *                    - 'insalubrite' (string): 'Oui' if is_insalubrite.
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
                if ('' !== trim($qualificationLabel)) {
                    $qualification[] = Qualification::tryFromLabel(trim($qualificationLabel));
                }
            }
        }

        $desordrePrecision->setLabel($data['label'])
        ->setDesordreCritere($data['desordreCritere'])
        ->setCoef((float) $coef)
        ->setDesordrePrecisionSlug($slug)
        ->setQualification($qualification);

        if (isset($data['danger'])) {
            $desordrePrecision->setIsDanger('Oui' === $data['danger']);
        }
        if (isset($data['suroccupation'])) {
            $desordrePrecision->setIsSuroccupation('Oui' === $data['suroccupation']);
        }
        if (isset($data['insalubrite'])) {
            $desordrePrecision->setIsInsalubrite('Oui' === $data['insalubrite']);
        }

        $this->save($desordrePrecision);

        return $desordrePrecision;
    }
}
