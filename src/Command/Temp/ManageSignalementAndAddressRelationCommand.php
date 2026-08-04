<?php

namespace App\Command\Temp;

use App\Entity\Address;
use App\Manager\HistoryEntryManager;
use App\Repository\AddressRepository;
use App\Repository\SignalementRepository;
use App\Service\Signalement\ZipcodeProvider;
use App\Utils\Address\AddressParser;
use App\Utils\StringHelper;
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
        private readonly SignalementRepository $signalementRepository,
        private readonly AddressRepository $addressRepository,
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

        $existingAddresses = $this->addressRepository->findAllIndexed();
        $addressKeysByBanId = [];
        foreach ($existingAddresses as $addressKey => $existingAddress) {
            if ($existingAddress->getBanId()) {
                $addressKeysByBanId[$existingAddress->getBanId()] = $addressKey;
            }
        }
        $addressesToProcess = $this->signalementRepository->findWithoutAddressId();
        $linkedCount = 0;
        $createdCount = 0;
        $errorCpInseeCount = 0;
        $errorInseeFormatCount = 0;
        $errorsTerritoriesIds = [];

        $io->progressStart(count($addressesToProcess));
        foreach ($addressesToProcess as $index => $row) {
            $signalement = $row[0];
            $territoryId = $row['territoryId'];
            $rawAddress = trim((string) $signalement->getAdresseOccupant());
            $villeOccupant = trim((string) $signalement->getVilleOccupant());
            $cpOccupant = trim((string) $signalement->getCpOccupant());
            $inseeOccupant = trim((string) $signalement->getInseeOccupant());
            $banId = trim((string) $signalement->getBanIdOccupant());

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

            if (0 === ($index + 1) % self::BATCH_SIZE) {
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

        /*if(count($errorsTerritoriesIds)) {
            $io->warning('Liste des signalements avec incohérence de territoire : '.implode(', ', $errorsTerritoriesIds));
        }*/

        return Command::SUCCESS;
    }

    public static function generateAddressKey(
        ?string $houseNumber,
        ?string $street,
        ?string $postCode,
        ?string $cityCode,
    ): string {
        return implode('|', array_map(
            StringHelper::normalize(...),
            [
                (string) $houseNumber,
                (string) $street,
                (string) $postCode,
                (string) $cityCode,
            ],
        ));
    }
}
