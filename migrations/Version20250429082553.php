<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250429082553 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update profile_declarant for signalement with create_from IS NULL';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE signalement SET profile_declarant = \'TIERS_PARTICULIER\' WHERE created_from_id IS NULL');
        $this->addSql('UPDATE signalement SET profile_declarant = \'TIERS_PRO\' WHERE created_from_id IS NULL AND lien_declarant_occupant IN (\'PROFESSIONNEL\', \'pro\', \'assistante sociale\', \'curatrice\')');
        $this->addSql('UPDATE signalement SET profile_declarant = \'LOCATAIRE\' WHERE created_from_id IS NULL AND is_not_occupant = 0');
    }
}
