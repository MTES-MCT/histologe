<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240902085930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'remove duplicates of tags';
    }

    public function up(Schema $schema): void
    {
        $tags = [
            ['keep' => 70, 'archive' => 78],
            ['keep' => 1227, 'archive' => 1143],
            ['keep' => 242, 'archive' => 2086],
            ['keep' => 174, 'archive' => 908],
            ['keep' => 906, 'archive' => 745],
            ['keep' => 261, 'archive' => 2171],
            ['keep' => 259, 'archive' => 1591],
            ['keep' => 2097, 'archive' => 1619],
            ['keep' => 1019, 'archive' => 2292],
            ['keep' => 1784, 'archive' => 2289],
            ['keep' => 942, 'archive' => 1670],
            ['keep' => 214, 'archive' => 1638],
            ['keep' => 2243, 'archive' => 2092],
            ['keep' => 2243, 'archive' => 2310],
            ['keep' => 1932, 'archive' => 2149],
            ['keep' => 153, 'archive' => 2246],
            ['keep' => 262, 'archive' => 2087],
            ['keep' => 127, 'archive' => 1207],
            ['keep' => 271, 'archive' => 2093],
            ['keep' => 120, 'archive' => 2239],
            ['keep' => 1688, 'archive' => 695],
            ['keep' => 426, 'archive' => 1824],
            ['keep' => 426, 'archive' => 2150],
            ['keep' => 1531, 'archive' => 1767],
            ['keep' => 1216, 'archive' => 1214],
            ['keep' => 932, 'archive' => 934],
            ['keep' => 1754, 'archive' => 2206],
            ['keep' => 1757, 'archive' => 2191],
            ['keep' => 1759, 'archive' => 2221],
            ['keep' => 1760, 'archive' => 1837],
            ['keep' => 1732, 'archive' => 1733],
            ['keep' => 1831, 'archive' => 1832],
            ['keep' => 2127, 'archive' => 2128],
            ['keep' => 2258, 'archive' => 2259],
            ['keep' => 1725, 'archive' => 1726],
            ['keep' => 792, 'archive' => 1396],
            ['keep' => 1534, 'archive' => 1618],
            ['keep' => 634, 'archive' => 1567],
            ['keep' => 1336, 'archive' => 2083],
            ['keep' => 889, 'archive' => 2170],
            ['keep' => 225, 'archive' => 1331],
            ['keep' => 1489, 'archive' => 822],
            ['keep' => 746, 'archive' => 719],
        ];

        foreach ($tags as $tag) {
            $keepTag = $this->connection->fetchAssociative(
                'SELECT * FROM tag WHERE id = :id',
                ['id' => $tag['keep']]
            );
            $archiveTag = $this->connection->fetchAssociative(
                'SELECT * FROM tag WHERE id = :id',
                ['id' => $tag['archive']]
            );

            if (!$keepTag || !$archiveTag || '1' == $archiveTag['is_archive'] || $keepTag['territory_id'] !== $archiveTag['territory_id']) {
                continue;
            }

            $existingAssociations = $this->connection->fetchAllAssociative(
                'SELECT * FROM tag_signalement WHERE tag_id = :idArchive',
                ['idArchive' => $tag['archive']]
            );
            foreach ($existingAssociations as $association) {
                $isAlreadyLinked = $this->connection->fetchAssociative(
                    'SELECT * FROM tag_signalement WHERE tag_id = :idKeep AND signalement_id = :idSignalement',
                    ['idKeep' => $tag['keep'], 'idSignalement' => $association['signalement_id']]
                );

                if ($isAlreadyLinked) {
                    $this->addSql(
                        'DELETE FROM tag_signalement WHERE tag_id = :idArchive AND signalement_id = :idSignalement',
                        ['idArchive' => $tag['archive'], 'idSignalement' => $association['signalement_id']]
                    );
                } else {
                    $this->addSql(
                        'UPDATE tag_signalement SET tag_id = :idKeep WHERE tag_id = :idArchive AND signalement_id = :idSignalement',
                        ['idKeep' => $tag['keep'], 'idArchive' => $tag['archive'], 'idSignalement' => $association['signalement_id']]
                    );
                }
            }

            $this->addSql('UPDATE tag SET is_archive = 1 WHERE id = :idArchive', ['idArchive' => $tag['archive']]);
        }
    }

    public function down(Schema $schema): void
    {
    }
}
