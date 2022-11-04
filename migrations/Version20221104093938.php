<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221104093938 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add authorized communes to territory';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE territory ADD authorized_codes_insee JSON DEFAULT NULL');
        $authorizedCodesInsee = [
            69091, 69096, 69123, 69149, 69199, 69205, 69290, 69259, 69266,
            69381, 69382, 69383, 69384, 69385, 69386, 69387, 69388, 69389,
            69901, ];
        $this->addSql('UPDATE territory SET authorized_codes_insee = \''.json_encode($authorizedCodesInsee).'\' WHERE zip = \'69\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE territory DROP authorized_codes_insee');
    }
}
