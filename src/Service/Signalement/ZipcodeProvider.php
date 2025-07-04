<?php

namespace App\Service\Signalement;

use App\Entity\Territory;
use App\Repository\TerritoryRepository;

class ZipcodeProvider
{
    private const string CORSE_DU_SUD_CODE_DEPARTMENT_2A = '2A';
    private const string HAUTE_CORSE_CODE_DEPARTMENT_2B = '2B';
    public const string RHONE_CODE_DEPARTMENT_69 = '69';
    public const string METROPOLE_LYON_CODE_DEPARTMENT_69A = '69A';
    private const string GUADELOUPE_CODE_DEPARTMENT_971 = '971';
    private const string MARTINIQUE_CODE_DEPARTMENT_972 = '972';
    private const string GUYANE_CODE_DEPARTMENT_973 = '973';
    private const string LA_REUNION_CODE_DEPARTMENT_974 = '974';
    private const string ST_PIERRE_ET_MIQUELON_CODE_DEPARTMENT_975 = '975';
    private const string MAYOTTE_CODE_DEPARTMENT_976 = '976';
    private const string SAINT_BARTHELEMY_CODE_DEPARTMENT_977 = '977';
    private const string SAINT_MARTIN_CODE_DEPARTMENT_978 = '978';
    private const string WALLIS_ET_FUTUNA_CODE_DEPARTMENT_986 = '986';
    private const string POLYNESIE_FRANCAISE_CODE_DEPARTMENT_987 = '987';
    private const string NOUVELLE_CALEDONIE_CODE_DEPARTMENT_988 = '988';
    /**
     * @var array<Territory>
     */
    private array $territories = [];

    public function __construct(private readonly TerritoryRepository $territoryRepository)
    {
        $this->territories = $this->territoryRepository->findAllIndexedByZip();
    }

    public function getTerritoryByInseeCode(string $inseeCode, bool $forceReload = false): ?Territory
    {
        if ($forceReload) {
            $this->territories = $this->territoryRepository->findAllIndexedByZip();
        }
        $zipCode = $this->getInternalZipCodeByInseeCode($inseeCode);

        return $this->territories[$zipCode] ?? null;
    }

    private function getInternalZipCodeByInseeCode(string $inseeCode): string
    {
        $inseeClean = str_pad($inseeCode, 5, '0', \STR_PAD_LEFT);
        $first3 = substr(trim($inseeClean), 0, 3);
        $first2 = substr(trim($inseeClean), 0, 2);

        if (self::RHONE_CODE_DEPARTMENT_69 === $first2) {
            if (in_array($inseeClean, $this->territories[self::METROPOLE_LYON_CODE_DEPARTMENT_69A]->getAuthorizedCodesInsee())) {
                return self::METROPOLE_LYON_CODE_DEPARTMENT_69A;
            }
        }

        return match ($first3) {
            '200', '201' => self::CORSE_DU_SUD_CODE_DEPARTMENT_2A,
            '202', '206' => self::HAUTE_CORSE_CODE_DEPARTMENT_2B,
            '971' => self::GUADELOUPE_CODE_DEPARTMENT_971,
            '972' => self::MARTINIQUE_CODE_DEPARTMENT_972,
            '973' => self::GUYANE_CODE_DEPARTMENT_973,
            '974' => self::LA_REUNION_CODE_DEPARTMENT_974,
            '975' => self::ST_PIERRE_ET_MIQUELON_CODE_DEPARTMENT_975,
            '976' => self::MAYOTTE_CODE_DEPARTMENT_976,
            '977' => self::SAINT_BARTHELEMY_CODE_DEPARTMENT_977,
            '978' => self::SAINT_MARTIN_CODE_DEPARTMENT_978,
            '986' => self::WALLIS_ET_FUTUNA_CODE_DEPARTMENT_986,
            '987' => self::POLYNESIE_FRANCAISE_CODE_DEPARTMENT_987,
            '988' => self::NOUVELLE_CALEDONIE_CODE_DEPARTMENT_988,
            default => $first2,
        };
    }
}
