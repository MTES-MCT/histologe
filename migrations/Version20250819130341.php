<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\SuiviCategory;
use App\Entity\Suivi;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250819130341 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'set suivi category as MESSAGE_USAGER_POST_CLOTURE when they are typed TYPE_USAGER_POST_CLOTURE';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE suivi SET category = \''.SuiviCategory::MESSAGE_USAGER_POST_CLOTURE->value.'\' WHERE type = '.Suivi::TYPE_USAGER_POST_CLOTURE);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE suivi SET category = \''.SuiviCategory::MESSAGE_USAGER->value.'\' WHERE category = \''.SuiviCategory::MESSAGE_USAGER_POST_CLOTURE->value.'\'');
    }
}
