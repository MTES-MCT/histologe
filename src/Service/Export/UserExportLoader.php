<?php

namespace App\Service\Export;

use App\Entity\Enum\UserStatus;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ListFilters\SearchUser;
use App\Utils\ExportFormat;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Options as CsvOptions;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

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

    public function load(SearchUser $searchUser, string $format, string $outputFilePath): void
    {
        if (ExportFormat::FORMAT_CSV === $format) {
            $writer = new CsvWriter(new CsvOptions(FIELD_DELIMITER: ExportFormat::CSV_SEPARATOR));
        } else {
            $writer = new XlsxWriter();
        }
        $writer->openToFile($outputFilePath);

        $columns = self::getColumnForUser($searchUser->getUser());
        $headers = array_map(static fn ($column) => $column['label'], $columns);
        $writer->addRow(Row::fromValues(array_values($headers)));
        /** @var array<int, User> $list */
        $list = $this->userRepository->findFiltered($searchUser);
        foreach ($list as $user) {
            $territories = implode(', ', array_map(
                static fn ($t) => $t->getZipAndName(),
                $user->getPartnersTerritories()
            ));
            $partners = implode(', ', array_map(
                static fn ($p) => $p->getNom(),
                $user->getPartners()->toArray()
            ));
            $partnerTypes = implode(', ', array_map(
                static fn ($p) => $p->getType() ? $p->getType()->label() : 'N/A',
                $user->getPartners()->toArray()
            ));
            $rowArray = array_map(static fn ($key) => match ($key) {
                'id' => $user->getId(),
                'territory' => $territories,
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'fonction' => $user->getFonction(),
                'partner' => $partners,
                'partnerType' => $partnerTypes,
                'createdAt' => $user->getCreatedAt()->format('d/m/Y'),
                'statut' => UserStatus::ACTIVE === $user->getStatut() ? 'Activé' : 'Non activé',
                'lastLoginAt' => $user->getLastLoginAt() ? $user->getLastLoginAt()->format('d/m/Y') : '',
                'role' => $user->getRoleLabel(),
                'permissionAffectation' => $user->isSuperAdmin() || $user->isTerritoryAdmin() || $user->hasPermissionAffectation() ? 'oui' : 'non',
                default => '',
            }, array_keys($columns));
            $writer->addRow(Row::fromValues($rowArray));
        }
        $writer->close();
    }

    /**
     * @return array<mixed>
     */
    public static function getColumnForUser(User $user): array
    {
        $columnsList = self::COLUMNS_LIST;
        if (!$user->isSuperAdmin()) {
            unset($columnsList['territory']);
        }

        return $columnsList;
    }
}
