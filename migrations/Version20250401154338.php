<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250401154338 extends AbstractMigration
{
    private const TERRITORY_PAU_ID = 102;
    private const TERRITORY_PYR_ATL_ID = 65;

    public function getDescription(): string
    {
        return 'remove Pau from territories';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE bailleur_territory SET territory_id = '.self::TERRITORY_PYR_ATL_ID.' WHERE territory_id = '.self::TERRITORY_PAU_ID);
        $this->addSql('UPDATE commune SET territory_id = '.self::TERRITORY_PYR_ATL_ID.' WHERE territory_id = '.self::TERRITORY_PAU_ID);
        $this->addSql('UPDATE partner SET territory_id = '.self::TERRITORY_PYR_ATL_ID.' WHERE territory_id = '.self::TERRITORY_PAU_ID);
        $this->addSql('UPDATE user SET territory_id = '.self::TERRITORY_PYR_ATL_ID.' WHERE territory_id = '.self::TERRITORY_PAU_ID);
        $this->addSql('UPDATE signalement SET territory_id = '.self::TERRITORY_PYR_ATL_ID.' WHERE territory_id = '.self::TERRITORY_PAU_ID);
        $this->addSql('UPDATE affectation SET territory_id = '.self::TERRITORY_PYR_ATL_ID.' WHERE territory_id = '.self::TERRITORY_PAU_ID);
        $this->addSql('DELETE FROM territory WHERE id = '.self::TERRITORY_PAU_ID);
    }

    public function down(Schema $schema): void
    {
    }
}
