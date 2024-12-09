<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241206111854 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add procedure suspectee to AutoAffectationRule';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auto_affectation_rule ADD procedures_suspectees LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', CHANGE allocataire allocataire VARCHAR(32) NOT NULL COMMENT \'Value possible all, non, oui, caf, msa or nsp\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auto_affectation_rule DROP procedures_suspectees, CHANGE allocataire allocataire VARCHAR(32) NOT NULL COMMENT \'Value possible all, non, oui, caf or msa\'');
    }
}
