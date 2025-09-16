<?php

namespace App\Factory;

use App\Dto\Request\Signalement\SignalementSearchQuery;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SignalementSearchQueryFactory
{
    public const string COOKIE_NAME = 'list-signalements-filters';

    public function __construct(
        private DenormalizerInterface $denormalizer,
        private ValidatorInterface $validator,
    ) {
    }

    public function createFromCookie(Request $httpRequest): ?SignalementSearchQuery
    {
        $cookieValue = $httpRequest->cookies->get(self::COOKIE_NAME);

        if (null === $cookieValue) {
            return null;
        }

        try {
            parse_str($cookieValue, $filteredData);

            // Conversion des types pour éviter les erreurs de désérialisation
            if (isset($filteredData['page'])) {
                $filteredData['page'] = (int) $filteredData['page'];
            }
            if (isset($filteredData['criticiteScoreMin'])) {
                $filteredData['criticiteScoreMin'] = (float) $filteredData['criticiteScoreMin'];
            }
            if (isset($filteredData['criticiteScoreMax'])) {
                $filteredData['criticiteScoreMax'] = (float) $filteredData['criticiteScoreMax'];
            }
            if (isset($filteredData['sansSuiviPeriode'])) {
                $filteredData['sansSuiviPeriode'] = (int) $filteredData['sansSuiviPeriode'];
            }
            if (isset($filteredData['usagerAbandonProcedure'])) {
                $filteredData['usagerAbandonProcedure'] = filter_var($filteredData['usagerAbandonProcedure'], \FILTER_VALIDATE_BOOLEAN);
            }

            $signalementSearchQuery = $this->denormalizer->denormalize(
                $filteredData,
                SignalementSearchQuery::class,
                null,
                ['allow_extra_attributes' => false]
            );

            $violations = $this->validator->validate($signalementSearchQuery);

            if (count($violations) > 0) {
                return null;
            }

            return $signalementSearchQuery;
        } catch (\Throwable) {
            return null;
        }
    }
}
