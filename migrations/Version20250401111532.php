<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250401111532 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add com_cloture column to signalement table and populate it with data from suivi table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD com_cloture LONGTEXT DEFAULT NULL');
        $list = $this->getSuiviCloturePourTous();
        foreach ($list as $item) {
            $exploded = explode('</strong>', $item['description']);
            $comCloture = $exploded[count($exploded) - 1];
            $this->addSql('UPDATE signalement SET com_cloture = ? WHERE id = ?', [$comCloture, $item['signalement_id']]);
        }
    }

    private function getSuiviCloturePourTous(): array
    {
        $query = 'SELECT signalement_id, description FROM suivi WHERE description LIKE "Le signalement a été cloturé pour tous les partenaires avec le motif suivant%"';

        return $this->connection->fetchAllAssociative($query);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP com_cloture');
    }
}
