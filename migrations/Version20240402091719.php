<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\ProfileDeclarant;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240402091719 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update profile declarant to legacy signalement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'UPDATE signalement SET profile_declarant = :profile_locataire WHERE created_from_id IS NULL AND is_not_occupant = 0',
            ['profile_locataire' => ProfileDeclarant::LOCATAIRE->value]
        );

        $this->addSql(
            'UPDATE signalement SET profile_declarant = :profile_tiers_pro WHERE created_from_id IS NULL AND is_not_occupant = 1 AND lien_declarant_occupant IN (\'PROFESSIONNEL\', \'pro\', \'assistante sociale\', \'curatrice\')',
            ['profile_tiers_pro' => ProfileDeclarant::TIERS_PRO->value]
        );

        $this->addSql(
            'UPDATE signalement SET profile_declarant = :profile_tiers_particulier WHERE created_from_id IS NULL AND is_not_occupant = 1 AND lien_declarant_occupant NOT IN (\'PROFESSIONNEL\', \'pro\', \'assistante sociale\', \'curatrice\')',
            ['profile_tiers_particulier' => ProfileDeclarant::TIERS_PARTICULIER->value]
        );
    }

    public function down(Schema $schema): void
    {
    }
}
