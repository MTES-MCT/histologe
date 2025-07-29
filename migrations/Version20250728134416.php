<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250728134416 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Change suivi category from AFFECTATION_IS_CLOSED to SIGNALEMENT_IS_CLOSED when description starts with 'Le signalement a été cloturé pour tous les partenaires'";
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE suivi SET category = 'SIGNALEMENT_IS_CLOSED' WHERE description like 'Le signalement a été cloturé pour tous les partenaires%' and category = 'AFFECTATION_IS_CLOSED'");
    }

    public function down(Schema $schema): void
    {
    }
}
