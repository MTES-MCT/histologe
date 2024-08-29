<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240829133834 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'migrate back old intervention dates';
    }

    public function up(Schema $schema): void
    {
        $visiteDates = [
            1 => '2017-09-29 13:40:12',
            2 => '2016-09-29 13:40:12',
        ];

        foreach ($visiteDates as $idVisite => $dateVisite) {
            $this->addSql('UPDATE intervention SET scheduled_at = :date WHERE id = :id', ['id' => $idVisite, 'date' => $dateVisite]);
        }
    }

    public function down(Schema $schema): void
    {
    }
}
