<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240726144731 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace primary key for Doctrine consitency';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX `primary` ON desordre_categorie_signalement');
        $this->addSql('ALTER TABLE desordre_categorie_signalement ADD PRIMARY KEY (signalement_id, desordre_categorie_id)');
        $this->addSql('DROP INDEX `primary` ON desordre_critere_signalement');
        $this->addSql('ALTER TABLE desordre_critere_signalement ADD PRIMARY KEY (signalement_id, desordre_critere_id)');
        $this->addSql('DROP INDEX `primary` ON desordre_precision_signalement');
        $this->addSql('ALTER TABLE desordre_precision_signalement ADD PRIMARY KEY (signalement_id, desordre_precision_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX `PRIMARY` ON desordre_categorie_signalement');
        $this->addSql('ALTER TABLE desordre_categorie_signalement ADD PRIMARY KEY (desordre_categorie_id, signalement_id)');
        $this->addSql('DROP INDEX `PRIMARY` ON desordre_critere_signalement');
        $this->addSql('ALTER TABLE desordre_critere_signalement ADD PRIMARY KEY (desordre_critere_id, signalement_id)');
        $this->addSql('DROP INDEX `PRIMARY` ON desordre_precision_signalement');
        $this->addSql('ALTER TABLE desordre_precision_signalement ADD PRIMARY KEY (desordre_precision_id, signalement_id)');
    }
}
