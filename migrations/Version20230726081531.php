<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230726081531 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add codes insee to Finistere';
    }

    public function up(Schema $schema): void
    {
        $codesInsee = [
            29232, 29019,
        ];

        $this->addSql(
            'UPDATE territory SET authorized_codes_insee = \''.json_encode($codesInsee).'\' WHERE zip = \'29\''
        );
    }

    public function down(Schema $schema): void
    {
    }
}
