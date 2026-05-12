<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\SuiviCategory;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260504151832 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set is_visible_for_bailleur = true for all suivis submitted by bailleur';
    }

    public function up(Schema $schema): void
    {
        foreach (SuiviCategory::CategoriesSubmittedByBailleur() as $category) {
            $this->addSql('UPDATE suivi SET is_visible_for_bailleur = 1 WHERE category = :category', ['category' => $category->value]);
        }
    }

    public function down(Schema $schema): void
    {
        foreach (SuiviCategory::CategoriesSubmittedByBailleur() as $category) {
            $this->addSql('UPDATE suivi SET is_visible_for_bailleur = 0 WHERE category = :category', ['category' => $category->value]);
        }
    }
}
