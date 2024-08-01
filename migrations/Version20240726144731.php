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
        $this->addSql('ALTER TABLE desordre_categorie_signalement DROP PRIMARY KEY, ADD PRIMARY KEY (signalement_id, desordre_categorie_id)');
        $this->addSql('ALTER TABLE desordre_critere_signalement DROP PRIMARY KEY, ADD PRIMARY KEY (signalement_id, desordre_critere_id)');
        $this->addSql('ALTER TABLE desordre_precision_signalement DROP PRIMARY KEY, ADD PRIMARY KEY (signalement_id, desordre_precision_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE desordre_categorie_signalement DROP PRIMARY KEY, ADD PRIMARY KEY (desordre_categorie_id, signalement_id)');
        $this->addSql('ALTER TABLE desordre_critere_signalement DROP PRIMARY KEY, ADD PRIMARY KEY (desordre_critere_id, signalement_id)');
        $this->addSql('ALTER TABLE desordre_precision_signalement DROP PRIMARY KEY, ADD PRIMARY KEY (desordre_precision_id, signalement_id)');
    }
}
