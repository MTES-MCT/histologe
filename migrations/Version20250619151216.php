<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\AffectationStatus;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250619151216 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change affectation status format';
    }

    private const OLD_STATUS_TO_NEW_STATUS = [
        AffectationStatus::WAIT->value => 0,
        AffectationStatus::ACCEPTED->value => 1,
        AffectationStatus::REFUSED->value => 2,
        AffectationStatus::CLOSED->value => 3,
    ];

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE affectation CHANGE statut statut VARCHAR(255) NOT NULL');

        foreach (self::OLD_STATUS_TO_NEW_STATUS as $newStatus => $oldStatus) {
            $this->addSql('UPDATE `affectation` SET `statut` = \''.$newStatus.'\' WHERE statut = \''.$oldStatus.'\'');
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::OLD_STATUS_TO_NEW_STATUS as $newStatus => $oldStatus) {
            $this->addSql('UPDATE `affectation` SET `statut` = \''.$oldStatus.'\' WHERE statut = \''.$newStatus.'\'');
        }

        $this->addSql('ALTER TABLE affectation CHANGE statut statut INT NOT NULL');
    }
}
