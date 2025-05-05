<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250505092515 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update profile_declarant for signalement where created_from_id and profile_declarant IS NULL';
    }

    public function up(Schema $schema): void
    {
        $baseWhere = 'WHERE created_from_id IS NULL AND profile_declarant IS NULL';
        $this->addSql('UPDATE signalement SET profile_declarant = \'LOCATAIRE\' '.$baseWhere.' AND is_not_occupant = 0');
        $this->addSql('UPDATE signalement SET profile_declarant = \'TIERS_PRO\' '.$baseWhere.' AND lien_declarant_occupant IN (\'PROFESSIONNEL\', \'pro\', \'assistante sociale\', \'curatrice\')');
        $this->addSql('UPDATE signalement SET profile_declarant = \'TIERS_PARTICULIER\' '.$baseWhere.'');
    }

    public function down(Schema $schema): void
    {
    }
}
