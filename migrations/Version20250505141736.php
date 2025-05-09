<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\UserStatus;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250505141736 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change user status format';
    }

    private const OLD_STATUS_TO_NEW_STATUS = [
        UserStatus::INACTIVE->value => 0,
        UserStatus::ACTIVE->value => 1,
        UserStatus::ARCHIVE->value => 2,
    ];

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user CHANGE statut statut VARCHAR(255) NOT NULL');

        foreach (self::OLD_STATUS_TO_NEW_STATUS as $newStatus => $oldStatus) {
            $this->addSql('UPDATE `user` SET `statut` = \''.$newStatus.'\' WHERE statut = \''.$oldStatus.'\'');
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::OLD_STATUS_TO_NEW_STATUS as $newStatus => $oldStatus) {
            $this->addSql('UPDATE `user` SET `statut` = \''.$oldStatus.'\' WHERE statut = \''.$newStatus.'\'');
        }

        $this->addSql('ALTER TABLE user CHANGE statut statut INT NOT NULL');
    }
}
