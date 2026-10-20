<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261019093835 extends AbstractMigration
{
    private const array DESCRIPTIONS = [
        'SIGNALEMENT_IS_INJONCTION' => 'Dossier déposé dans le cadre de la démarche accélérée',
    ];

    public function getDescription(): string
    {
        return 'Empty description in suivi table for SIGNALEMENT_IS_INJONCTION category';
    }

    public function up(Schema $schema): void
    {
        foreach (array_keys(self::DESCRIPTIONS) as $category) {
            $this->addSql('UPDATE suivi SET description = \'\' WHERE category = :category', ['category' => $category]);
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::DESCRIPTIONS as $category => $description) {
            $this->addSql(
                'UPDATE suivi SET description = :description WHERE category = :category',
                ['description' => $description, 'category' => $category]
            );
        }
    }
}
