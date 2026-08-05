<?php

namespace App\Command\Temp;

use App\Entity\Address;
use App\Entity\Signalement;
use App\Manager\HistoryEntryManager;
use App\Service\Signalement\ZipcodeProvider;
use App\Utils\Address\AddressParser;
use Doctrine\ORM\EntityManagerInterface;
use LongitudeOne\Spatial\PHP\Types\Geometry\Point;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:manage-signalement-and-address-relation',
    description: 'Copy signalement addresses to Address entities and create the relation',
)]
class ManageSignalementAndAddressRelationCommand extends Command
{
    private const int BATCH_SIZE = 500;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ZipcodeProvider $zipCodeProvider,
        private readonly HistoryEntryManager $historyEntryManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->historyEntryManager->removeEntityListeners();

        $existingAddresses = $this->findAllAddressesIndexed();
        $addressKeysByBanId = [];
        foreach ($existingAddresses as $addressKey => $existingAddress) {
            if ($existingAddress->getBanId()) {
                $addressKeysByBanId[$existingAddress->getBanId()] = $addressKey;
            }
        }
        $addressesToProcess = $this->findSignalementsWithoutAddressId();
        $linkedCount = 0;
        $createdCount = 0;
        $errorCpInseeCount = 0;
        $errorInseeFormatCount = 0;
        $errorsTerritoriesIds = [];

        $io->progressStart(count($addressesToProcess));
        $counter = 0;
        foreach ($addressesToProcess as $row) {
            $signalement = $row[0];
            $territoryId = $row['territoryId'];
            $rawAddress = trim((string) $signalement->getAdresseOccupantDeprecated());
            $villeOccupant = trim((string) $signalement->getVilleOccupantDeprecated());
            $cpOccupant = trim((string) $signalement->getCpOccupantDeprecated());
            $inseeOccupant = trim((string) $signalement->getInseeOccupantDeprecated());
            $banId = trim((string) $signalement->getBanIdOccupantDeprecated());

            if ((!$cpOccupant && !$inseeOccupant) || ('#N/D' == $cpOccupant && '#N/D' == $inseeOccupant)) {
                ++$errorCpInseeCount;
                continue;
            }

            if ($inseeOccupant && 5 !== strlen($inseeOccupant)) {
                ++$errorInseeFormatCount;
                continue;
            }

            $parsedAddress = AddressParser::parse($rawAddress);
            $houseNumber = $parsedAddress['number'];
            if ($houseNumber && $parsedAddress['suffix']) {
                $houseNumber .= ' '.$parsedAddress['suffix'];
            }

            $addressKey = self::generateAddressKey(
                $houseNumber,
                $parsedAddress['street'],
                $cpOccupant,
                $inseeOccupant,
            );
            if ($banId && isset($addressKeysByBanId[$banId]) && $addressKey !== $addressKeysByBanId[$banId]) {
                // si l'adresse existe déja il faut insérer le banId
                if (isset($existingAddresses[$addressKey])) {
                    // on supprime en base l'ancienne adresse qui n'est plus utilisée les relations signalement->adresse seront mis jour au lancement suivant de la commande
                    $oldAddressKey = $addressKeysByBanId[$banId];
                    $addressToRemove = $existingAddresses[$oldAddressKey];
                    $this->entityManager->remove($addressToRemove);
                    unset($existingAddresses[$oldAddressKey]);
                    $addressKeysByBanId[$banId] = $addressKey;
                    $this->entityManager->flush();
                    $existingAddresses[$addressKey]->setBanId($banId);
                } else {
                    // on considere que cest toujours la version la plus récente de la BAN qui est correcte, donc on met à jour l'adresse avec les nouvelles informations de la BAN
                    // Attention cela change certains code postaux et donc a terme certaines informations de connexion sur les signalements (quand le champ cpOccupant sera supprimé du signalement)
                    $oldAddressKey = $addressKeysByBanId[$banId];
                    $address = $existingAddresses[$oldAddressKey];
                    $address->setHousenumber($houseNumber ? trim($houseNumber) : null);
                    $address->setStreet(trim($parsedAddress['street']));
                    $address->setCity($villeOccupant);
                    $address->setPostCode($cpOccupant);
                    $address->setCityCode($inseeOccupant);
                    $address->setBanId($banId);
                    $geoloc = $signalement->getGeoloc();
                    if (isset($geoloc['lng'], $geoloc['lat'])) {
                        $address->setPoint(new Point((float) $geoloc['lng'], (float) $geoloc['lat']));
                    }
                    $existingAddresses[$addressKey] = $address;
                    unset($existingAddresses[$oldAddressKey]);
                    $addressKeysByBanId[$banId] = $addressKey;
                }
            }
            if (isset($existingAddresses[$addressKey])) {
                if ($territoryId != $existingAddresses[$addressKey]->getTerritory()->getId()) {
                    $errorsTerritoriesIds[] = $signalement->getId();
                    continue;
                }
                $signalement->setAddress($existingAddresses[$addressKey]);
                ++$linkedCount;
            } else {
                $calculatedTerritory = $this->zipCodeProvider->getTerritoryByInseeCode($inseeOccupant);
                if (!$calculatedTerritory) {
                    $calculatedTerritory = $this->zipCodeProvider->getTerritoryByPostalCode($cpOccupant);
                }
                if (!$calculatedTerritory) {
                    throw new \Exception('Impossible de déterminer le territoire du signalement '.$signalement->getId().' à partir du code INSEE '.$inseeOccupant.' ou du code postal '.$cpOccupant);
                }
                if ($calculatedTerritory->getId() != $territoryId) {
                    $errorsTerritoriesIds[] = $signalement->getId();
                    continue;
                }

                $address = new Address();
                $address->setHousenumber($houseNumber ? trim($houseNumber) : null);
                $address->setStreet(trim($parsedAddress['street']));
                $address->setCity($villeOccupant);
                $address->setPostCode($cpOccupant);
                $address->setCityCode($inseeOccupant);
                $address->setBanId('' !== $banId && '0' !== $banId ? $banId : null);
                $this->

                $geoloc = $signalement->getGeoloc();
                if (isset($geoloc['lng'], $geoloc['lat'])) {
                    $address->setPoint(new Point((float) $geoloc['lng'], (float) $geoloc['lat']));
                }

                $address->setTerritory($calculatedTerritory);
                $this->entityManager->persist($address);

                $signalement->setAddress($address);
                ++$createdCount;

                $existingAddresses[$addressKey] = $address;
                if ('' !== $banId && '0' !== $banId) {
                    $addressKeysByBanId[$banId] = $addressKey;
                }
            }

            ++$counter;
            if (0 === ($counter % self::BATCH_SIZE)) {
                $this->entityManager->flush();
            }
            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $io->progressFinish();

        $io->success(sprintf('
        Traitement terminé : 
        %d signalements liés à des adresses existantes, 
        %d nouvelles adresses créées, 
        %d Incohérences de territoire, 
        %d Absence de code postal et de code INSEE, 
        %d erreurs de format INSEE.',
            $linkedCount,
            $createdCount,
            count($errorsTerritoriesIds),
            $errorCpInseeCount,
            $errorInseeFormatCount
        ));

        return Command::SUCCESS;
    }

    private static function generateAddressKey(
        ?string $houseNumber,
        ?string $street,
        ?string $postCode,
        ?string $cityCode,
    ): string {
        return implode('|', array_map(
            self::normalize(...),
            [
                (string) $houseNumber,
                (string) $street,
                (string) $postCode,
                (string) $cityCode,
            ],
        ));
    }

    /** @return array<string, Address> */
    private function findAllAddressesIndexed(): array
    {
        $addresses = $this->entityManager->getRepository(Address::class)->findAll();
        $indexedAddresses = [];

        foreach ($addresses as $address) {
            $key = self::generateAddressKey(
                $address->getHousenumber(),
                $address->getStreet(),
                $address->getPostCode(),
                $address->getCityCode(),
            );
            $indexedAddresses[$key] = $address;
        }

        return $indexedAddresses;
    }

    /** @return array<int, array{0: Signalement, territoryId: int|null}> */
    private function findSignalementsWithoutAddressId(): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('partial s.{id, adresseOccupant, cpOccupant, villeOccupant, banIdOccupant, inseeOccupant, geoloc, statut}')
            ->from(Signalement::class, 's')
            ->addSelect('signalementUsager')
            ->leftJoin('s.signalementUsager', 'signalementUsager')
            ->addSelect('IDENTITY(s.territory) AS territoryId')
            ->where('s.address IS NULL')
            ->andWhere('(s.cpOccupant IS NOT NULL AND s.cpOccupant != \'\' AND s.cpOccupant != \'#N/D\') OR (s.inseeOccupant IS NOT NULL AND s.inseeOccupant != \'\' AND s.inseeOccupant != \'#N/D\')')
            ->setMaxResults(6000)
            ->getQuery()
            ->getResult();
    }

    private static function normalize(string $str): string
    {
        $normalized = strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str));
        $normalized = str_replace(['-', "'"], ' ', $normalized);

        return trim((string) preg_replace('/\s+/', ' ', $normalized));
    }
}
