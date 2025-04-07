<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ListFilters\SearchUser;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

readonly class UserExportLoader
{
    public const COLUMNS_LIST = [
        'id' => ['label' => 'ID', 'desc' => 'l\'identifiant du compte'],
        'territory' => ['label' => 'Territoire', 'desc' => 'Département du compte'],
        'email' => ['label' => 'Email', 'desc' => 'l\'adresse email du compte'],
        'nom' => ['label' => 'Nom', 'desc' => 'le nom de l\'agent'],
        'prenom' => ['label' => 'Prénom', 'desc' => 'le prénom de l\'agent'],
        'fonction' => ['label' => 'Fonction', 'desc' => 'la fonction de l\'agent'],
        'partner' => ['label' => 'Partenaire', 'desc' => 'le nom du partenaire auquel l\'agent appartient'],
        'partnerType' => ['label' => 'Type du partenaire', 'desc' => 'le type du partenaire auquel l\'agent appartient'],
        'createdAt' => ['label' => 'Date de création', 'desc' => 'la date de création du compte'],
        'statut' => ['label' => 'Statut', 'desc' => 'le statut du compte (s\'il est activé ou non)'],
        'lastLoginAt' => ['label' => 'Dernière connexion', 'desc' => 'la date de dernière connexion au compte'],
        'role' => ['label' => 'Rôle', 'desc' => 's\'il s\'agit d\'un compte agent, Admin. partenaire ou responsable de territoire'],
        'permissionAffectation' => ['label' => 'Droit d\'affectation', 'desc' => 'si ce compte peut affecter des partenaires à un signalement'],
    ];

    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function load(SearchUser $searchUser): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = array_map(fn ($column) => $column['label'], self::getColumnForUser($searchUser->getUser()));
        $sheetData = [$headers];
        $list = $this->userRepository->findFiltered($searchUser);
        foreach ($list as $user) {
            $rowArray = [];
            foreach ($headers as $key => $unused) {
                $territories = '';
                foreach ($user->getPartnersTerritories() as $territory) {
                    $territories .= $territory->getZip().' - '.$territory->getName().', ';
                }
                $territories = substr($territories, 0, -2);
                $partners = '';
                $partnerTypes = '';
                foreach ($user->getPartners() as $partner) {
                    $partners .= $partner->getNom().', ';
                    if ($partner->getType()) {
                        $partnerTypes .= $partner->getType()->label().', ';
                    } else {
                        $partnerTypes .= 'N/A, ';
                    }
                }
                $partners = substr($partners, 0, -2);
                $partnerTypes = substr($partnerTypes, 0, -2);
                $rowArray[] = match ($key) {
                    'id' => $user->getId(),
                    'territory' => $territories,
                    'email' => $user->getEmail(),
                    'nom' => $user->getNom(),
                    'prenom' => $user->getPrenom(),
                    'fonction' => $user->getFonction(),
                    'partner' => $partners,
                    'partnerType' => $partnerTypes,
                    'createdAt' => $user->getCreatedAt()->format('d/m/Y'),
                    'statut' => User::STATUS_ACTIVE === $user->getStatut() ? 'Activé' : 'Non activé',
                    'lastLoginAt' => $user->getLastLoginAt() ? $user->getLastLoginAt()->format('d/m/Y') : '',
                    'role' => $user->getRoleLabel(),
                    'permissionAffectation' => $user->isSuperAdmin() || $user->isTerritoryAdmin() || $user->hasPermissionAffectation() ? 'oui' : 'non',
                    default => '',
                };
            }
            $sheetData[] = $rowArray;
        }

        $sheet->fromArray($sheetData);

        return $spreadsheet;
    }

    public static function getColumnForUser(User $user): array
    {
        $columnsList = self::COLUMNS_LIST;
        if (!$user->isSuperAdmin()) {
            unset($columnsList['territory']);
        }

        return $columnsList;
    }
}
