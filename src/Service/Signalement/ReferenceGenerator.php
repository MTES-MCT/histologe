<?php

namespace App\Service\Signalement;

use App\Entity\Territory;
use App\Repository\SignalementRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\TransactionRequiredException;

class ReferenceGenerator
{
    public function __construct(private readonly SignalementRepository $signalementRepository)
    {
    }

    /**
     * @throws TransactionRequiredException
     * @throws NonUniqueResultException
     */
    public function generate(Territory $territory, bool $isDefinitive = true): string
    {
        $todayYear = (new \DateTime())->format('Y');

        if (!$isDefinitive) {
            return $todayYear.'-TEMPORAIRE';
        }

        $result = $this->signalementRepository->findLastReferenceByTerritory($territory);
        if (!empty($result)) {
            list($year, $id) = explode('-', $result['reference']);

            $id = (int) $id;
            ++$id;
            // s'il y a eu des erreurs d'année dans les références, on ne les prolonge pas
            if ($year !== $todayYear) {
                $year = $todayYear;
            }

            return $year.'-'.$id;
        }

        return $todayYear.'-'. 1;
    }
}
