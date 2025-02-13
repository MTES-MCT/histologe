<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\SignalementStatus;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250203165635 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'change signalement statut to enum';
    }

    private const OLD_STATUS_TO_NEW_STATUS = [
        SignalementStatus::DRAFT->value => 0,
        SignalementStatus::NEED_VALIDATION->value => 1,
        SignalementStatus::ACTIVE->value => 2,
        SignalementStatus::CLOSED->value => 6,
        SignalementStatus::ARCHIVED->value => 7,
        SignalementStatus::REFUSED->value => 8,
    ];

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement CHANGE statut statut VARCHAR(255) NOT NULL');

        foreach (self::OLD_STATUS_TO_NEW_STATUS as $newStatus => $oldStatus) {
            $this->addSql('UPDATE `signalement` SET `statut` = \''.$newStatus.'\' WHERE statut = \''.$oldStatus.'\'');
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::OLD_STATUS_TO_NEW_STATUS as $newStatus => $oldStatus) {
            $this->addSql('UPDATE `signalement` SET `statut` = \''.$oldStatus.'\' WHERE statut = \''.$newStatus.'\'');
        }

        $this->addSql('ALTER TABLE signalement CHANGE statut statut INT NOT NULL');
    }
}
