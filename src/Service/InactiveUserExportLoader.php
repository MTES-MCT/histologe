<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

readonly class InactiveUserExportLoader
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function load(User $user): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['ID', 'Nom', 'Prénom', 'Email', 'Partenaire', 'Date de création', 'Dernière connexion', 'Date d\'archivage prévue'];
        $sheetData = [$headers];
        $list = $this->userRepository->findUsersPendingToArchive($user);
        foreach ($list as $item) {
            $rowArray = [
                $item->getId(),
                $item->getNom(),
                $item->getPrenom(),
                $item->getEmail(),
                $item->getPartnerinTerritory($user->getFirstTerritory())?->getNom() ?? '',
                $item->getCreatedAt()->format('d/m/Y'),
                $item->getLastLoginAt() ? $item->getLastLoginAt()->format('d/m/Y') : '',
                $item->getArchivingScheduledAt() ? $item->getArchivingScheduledAt()->format('d/m/Y') : '',
            ];
            $sheetData[] = $rowArray;
        }
        $sheet->fromArray($sheetData);

        return $spreadsheet;
    }
}
