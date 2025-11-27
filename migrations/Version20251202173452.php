<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251202173452 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set bailleur_prevenu_at à J-2 mois (1er jour du mois à 00:00:00) pour les drafts EN_COURS avec id 76622, 80290, 81711';
    }

    public function up(Schema $schema): void
    {
        $this->skipIf('histologe' !== getenv('APP_ENV'), 'This migration should only be run in the histologe environment.');
        $this->addSql(<<<'SQL'
            UPDATE signalement_draft
            SET bailleur_prevenu_at = CONCAT(
                    DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 2 MONTH), '%Y-%m-01'),
                    ' 00:00:00'
                )
            WHERE id IN (76622, 80290, 81711)
              AND status = 'EN_COURS'
         SQL
        );
    }

    public function down(Schema $schema): void
    {
    }
}
