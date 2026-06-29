<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\SuiviCategory;
use App\Service\Signalement\Suivi\SuiviDescriptionHelper;
use App\Service\Signalement\Suivi\SuiviRecipient;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260629085411 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set description to empty string for suivi with categories having generic messages';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE suivi SET description = '' WHERE category = 'ASK_FEEDBACK_SENT'");
        $this->addSql("UPDATE suivi SET description = '' WHERE category = 'SIGNALEMENT_IS_ACTIVE'");
        $this->addSql("UPDATE suivi SET description = '' WHERE category = 'AFFECTATION_IS_ACCEPTED'");
        $this->addSql("UPDATE suivi SET description = '' WHERE category = 'INTERVENTION_IS_REQUIRED'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE suivi SET description = '".SuiviDescriptionHelper::getSpecificDescriptionForCategoryAndRecipient(SuiviCategory::ASK_FEEDBACK_SENT, SuiviRecipient::DEFAULT)."' WHERE category = 'ASK_FEEDBACK_SENT'");
        $this->addSql("UPDATE suivi SET description = '".SuiviDescriptionHelper::getSpecificDescriptionForCategoryAndRecipient(SuiviCategory::SIGNALEMENT_IS_ACTIVE, SuiviRecipient::DEFAULT)."' WHERE category = 'SIGNALEMENT_IS_ACTIVE'");
        $this->addSql("UPDATE suivi SET description = '".SuiviDescriptionHelper::getSpecificDescriptionForCategoryAndRecipient(SuiviCategory::AFFECTATION_IS_ACCEPTED, SuiviRecipient::DEFAULT)."' WHERE category = 'AFFECTATION_IS_ACCEPTED'");
        $this->addSql("UPDATE suivi SET description = '".SuiviDescriptionHelper::getSpecificDescriptionForCategoryAndRecipient(SuiviCategory::INTERVENTION_IS_REQUIRED, SuiviRecipient::DEFAULT)."' WHERE category = 'INTERVENTION_IS_REQUIRED'");
    }
}
