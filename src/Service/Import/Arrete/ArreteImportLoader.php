<?php

namespace App\Service\Import\Arrete;

use App\Entity\Arrete;
use App\Entity\User;
use App\Factory\ArreteFactory;
use App\Repository\ArreteRepository;
use App\Service\Gouv\Ban\AddressService;
use App\Service\Import\CsvParser;
use App\Service\Signalement\ZipcodeProvider;
use Doctrine\ORM\EntityManagerInterface;
use LongitudeOne\Spatial\Exception\InvalidValueException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ArreteImportLoader
{
    /** @var array<string, string[]> */
    private array $metadata = [];

    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly AddressService $addressService,
        private readonly ArreteFactory $arreteFactory,
        private readonly EntityManagerInterface $entityManager,
        private readonly ArreteRepository $arreteRepository,
        private readonly ZipcodeProvider $zipcodeProvider,
    ) {
    }

    /**
     * @return array{string[], ArreteImportRow[]}
     */
    public function validate(string $filepath, User $user): array
    {
        $errors = [];
        $validRows = [];
        $invalidLineNumbers = [];
        $detailedErrors = [];
        $countAddressToValidate = 0;
        $csvParser = new CsvParser()
            ->setFirstLine(ArreteImportRow::FIRST_LINE)
            ->autoDetectDelimiter($filepath);
        $data = $csvParser->parseAsDict($filepath);
        foreach ($data as $index => $row) {
            $arreteImportRow = new ArreteImportRow()
                ->setDateArrete($row[ArreteImportHeader::DATE_ARRETE] ?? null)
                ->setClassificationArrete($row[ArreteImportHeader::CLASSIFICATION_ARRETE] ?? null)
                ->setNumeroVoie($row[ArreteImportHeader::NUMERO_VOIE] ?? null)
                ->setNomVoie($row[ArreteImportHeader::NOM_VOIE] ?? null)
                ->setCodePostal($row[ArreteImportHeader::CODE_POSTAL] ?? null)
                ->setCommune($row[ArreteImportHeader::COMMUNE] ?? null)
                ->setDenominationSyndic($row[ArreteImportHeader::DENOMINATION_SYNDIC] ?? null)
                ->setIdentifiantParcellaire($row[ArreteImportHeader::ID_PARCELLAIRE] ?? null);

            if (!empty($row[ArreteImportHeader::DATE_ARRETE_MAIN_LEVEE])) {
                $arreteImportRow->setDateArreteMainLevee($row[ArreteImportHeader::DATE_ARRETE_MAIN_LEVEE]);
            }

            $violations = $this->validator->validate($arreteImportRow);
            if ($violations->count() > 0) {
                $lineNumber = $index + ArreteImportRow::FIRST_LINE + 1;
                $invalidLineNumbers[] = $lineNumber;
                foreach ($violations as $violation) {
                    $detailedErrors[] = sprintf('Ligne %d : %s', $lineNumber, $violation->getMessage());
                }
                continue;
            }

            $addressToValidate = $this->shouldValidateAddress($arreteImportRow, $user);
            $arreteImportRow->setAddressToValidate($addressToValidate);
            if ($addressToValidate) {
                ++$countAddressToValidate;
            }

            $validRows[] = $arreteImportRow;
        }

        if ($countAddressToValidate > 0) {
            $errors[] = sprintf('%d adresses à valider sur %d.', $countAddressToValidate, count($validRows));
        }

        if (!empty($invalidLineNumbers)) {
            $lineLabel = count($invalidLineNumbers) > 1 ? 'lignes' : 'ligne';
            $errors[] = sprintf(
                '%d %s présentent une erreur de format et ne pourront pas être importées : %s %s. Détails ci-dessous.',
                count($invalidLineNumbers),
                $lineLabel,
                $lineLabel,
                implode('; ', $invalidLineNumbers),
            );
            $errors = array_merge($errors, $detailedErrors);
        }

        return [$errors, $validRows];
    }

    /**
     * @param ArreteImportRow[] $data
     *
     * @return Arrete[]
     *
     * @throws InvalidValueException
     */
    public function load(array $data, User $user): array
    {
        $this->metadata = [];
        $this->metadata['countSuccess'] = 0;
        $arretes = [];
        foreach ($data as $index => $arreteImportRow) {
            if (!$this->validateImportRow($arreteImportRow, $index)) {
                continue;
            }

            $arrete = $this->arreteFactory->createInstanceFrom($arreteImportRow, $user);
            if (null === $arrete) {
                $dateArrete = $arreteImportRow->getDateArrete();
                $dateMainLevee = $arreteImportRow->getDateArreteMainLevee();
                $classification = $arreteImportRow->getClassificationArrete() ?: 'sans classification';
                $address = $arreteImportRow->getAddress() ?: 'une adresse inconnue';
                $this->metadata['errors'][] = sprintf(
                    'L\'arrêté %s du %s, %s, situé au %s ne peut pas être importé',
                    $classification,
                    $dateArrete?->format('d/m/Y') ?? 'date inconnue',
                    null !== $dateMainLevee ? 'avec main levée' : 'sans main levée',
                    $address,
                );
                continue;
            }

            $existingArrete = $this->arreteRepository->findOneByCriteria(
                $arrete->getDateArrete(),
                $arrete->getArreteType(),
                $arrete->getIdentifiantParcellaire(),
                $arrete->getAddress(),
                $arrete->getDateMainLevee(),
                $arrete->getSyndic(),
            );

            if ($existingArrete) {
                $this->metadata['errors'][] = sprintf(
                    'L\'arrêté %s du %s %s situé au %s a déjà été importé.',
                    $arrete->getArreteType()->label(),
                    $arrete->getDateArrete()->format('d/m/Y'),
                    $arrete->isMainLevee() ? 'avec main levée' : 'sans main levée',
                    $arrete->getAddress()->getFull()
                );
                continue;
            }

            ++$this->metadata['countSuccess'];
            $this->entityManager->persist($arrete);
            $this->entityManager->flush();
            $arretes[] = $arrete;
        }

        return $arretes;
    }

    /**
     * @return array<string, string[]>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    private function shouldValidateAddress(
        ArreteImportRow $arreteImportRow,
        User $user,
    ): bool {
        $address = $this->addressService->getAddress($arreteImportRow->getAddress());

        if ($address->getScore() < AddressService::SCORE_IF_BAN_ID_ACCEPTED) {
            return true;
        }

        $inseeCode = $address->getInseeCode();
        if (!$inseeCode) {
            return true;
        }

        $territoryAddress = $this->zipcodeProvider->getTerritoryByInseeCode($inseeCode);
        if (null === $territoryAddress) {
            return true;
        }

        if (!$user->isTerritoryAdmin()) {
            return false;
        }

        $userTerritory = $user->getFirstTerritory();

        return null === $userTerritory
            || $userTerritory->getId() !== $territoryAddress->getId();
    }

    private function validateImportRow(ArreteImportRow $arreteImportRow, int $index): bool
    {
        $violations = $this->validator->validate($arreteImportRow);

        if (0 === $violations->count()) {
            return true;
        }

        foreach ($violations as $violation) {
            $this->metadata['errors'][] = sprintf(
                'Ligne %d : %s - %s',
                $index + ArreteImportRow::FIRST_LINE + 1,
                $violation->getPropertyPath(),
                $violation->getMessage()
            );
        }

        return false;
    }
}
