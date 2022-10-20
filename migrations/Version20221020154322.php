<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221020154322 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix obsolete motif_cloture';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE signalement SET motif_cloture = "NON_DECENCE" WHERE motif_cloture="INDECENCE"');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
