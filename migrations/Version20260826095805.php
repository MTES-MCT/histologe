<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260826095805 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert remaining utf8mb3 tables/columns to utf8mb4 (emojis support)';
    }

    /**
     * Tables ordered by ascending size: small ones first, job_event (~3 GB) last.
     */
    private const array TABLES = [
        'doctrine_migration_versions',
        'desordre_categorie',
        'desordre_critere',
        'desordre_precision',
        'bailleur',
        'bailleur_territory',
        'epci',
        'commune',
        'signalement_usager',
        'intervention',
        'signalement_qualification',
        'desordre_precision_signalement',
        'file',
        'signalement_draft',
        'job_event',
    ];

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        foreach (self::TABLES as $table) {
            $this->addSql(sprintf('ALTER TABLE %s CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $table));
        }
    }

    public function down(Schema $schema): void
    {
        foreach (array_reverse(self::TABLES) as $table) {
            $this->addSql(sprintf('ALTER TABLE %s CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci', $table));
        }

        $this->addSql('ALTER DATABASE CHARACTER SET utf8mb3 COLLATE utf8mb3_bin');
    }
}
