<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260512153353 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'migrate signalement.situationFoyer.travailleurSocialAccompagnementNomStructure to existing signalement.structureReferentSocial';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE signalement SET structure_referent_social = JSON_UNQUOTE(JSON_EXTRACT(situation_foyer, "$.travailleurSocialAccompagnementNomStructure")) WHERE JSON_EXTRACT(situation_foyer, "$.travailleurSocialAccompagnementNomStructure") IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
    }
}
