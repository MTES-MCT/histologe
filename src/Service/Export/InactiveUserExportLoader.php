<?php

namespace App\Service\Export;

use App\Entity\User;
use App\Messenger\Message\ListExportMessage;
use App\Repository\UserRepository;
use App\Service\Signalement\Export\SignalementExportHeader;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Options as CsvOptions;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

readonly class InactiveUserExportLoader
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function load(User $user, string $format, string $outputFilePath): void
    {
        if (ListExportMessage::FORMAT_CSV === $format) {
            $writer = new CsvWriter(new CsvOptions(FIELD_DELIMITER: SignalementExportHeader::SEPARATOR));
        } else {
            $writer = new XlsxWriter();
        }
        $writer->openToFile($outputFilePath);

        $headers = ['ID', 'Nom', 'Prénom', 'Email', 'Partenaire', 'Date de création', 'Dernière connexion', 'Date d\'archivage prévue'];
        $writer->addRow(Row::fromValues($headers));
        /** @var array<int, User> $list */
        $list = $this->userRepository->findUsersPendingToArchive($user);
        foreach ($list as $item) {
            $partnerName = '';
            if ($user->isSuperAdmin()) {
                foreach ($item->getPartners() as $partner) {
                    $partnerName = $partner->getNom().', ';
                }
                $partnerName = rtrim($partnerName, ', ');
            } else {
                $partnerName = $item->getPartnerinTerritory($user->getFirstTerritory())?->getNom() ?? '';
            }
            $rowArray = [
                $item->getId(),
                $item->getNom(),
                $item->getPrenom(),
                $item->getEmail(),
                $partnerName,
                $item->getCreatedAt()->format('d/m/Y'),
                $item->getLastLoginAt() ? $item->getLastLoginAt()->format('d/m/Y') : '',
                $item->getArchivingScheduledAt() ? $item->getArchivingScheduledAt()->format('d/m/Y') : '',
            ];
            $writer->addRow(Row::fromValues($rowArray));
        }

        $writer->close();
    }
}
