<?php

namespace App\Service\Signalement;

use App\Entity\Territory;
use App\Repository\Query\Signalement\SignalementReferenceQuery;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\TransactionRequiredException;

class ReferenceGenerator
{
    public function __construct(private readonly SignalementReferenceQuery $signalementReferenceQuery)
    {
    }

    /**
     * @throws TransactionRequiredException
     * @throws NonUniqueResultException
     */
    public function generateReference(Territory $territory, bool $isDefinitive = true): string
    {
        $todayYear = (new \DateTime())->format('Y');

        if (!$isDefinitive) {
            return $todayYear.'-TEMPORAIRE';
        }

        $result = $this->signalementReferenceQuery->findLastReferenceByTerritory($territory);
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

    public function generateReferenceInjonction(): int
    {
        $maxRef = $this->signalementReferenceQuery->findMaxReferenceInjonction();
        if ($maxRef) {
            return $maxRef + 1;
        }

        return 2363;
    }
}
