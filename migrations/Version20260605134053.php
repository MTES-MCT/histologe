<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260605134053 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add insalubrite to desordre precisions manque eau chaude';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE desordre_precision SET is_insalubrite = 1, qualification = :qualification WHERE desordre_precision_slug = :slug', [
            'qualification' => json_encode(['RSD', 'NON_DECENCE', 'INSALUBRITE']),
            'slug' => 'desordres_logement_eau_eau_chaude',
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE desordre_precision SET is_insalubrite = 0, qualification = :qualification WHERE desordre_precision_slug = :slug', [
            'qualification' => json_encode(['RSD', 'NON_DECENCE']),
            'slug' => 'desordres_logement_eau_eau_chaude',
        ]);
    }
}
