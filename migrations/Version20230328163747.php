<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Notification;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230328163747 extends AbstractMigration
{
    public const DESC_SIGNALEMENT_VALIDE = '%Signalement validé%';

    public function getDescription(): string
    {
        return 'Remove legacy notification';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'DELETE n FROM notification n INNER JOIN suivi s on s.id = n.suivi_id WHERE s.description like "'.self::DESC_SIGNALEMENT_VALIDE.'"'
        );

        $this->addSql('DELETE FROM notification WHERE type IN ('.Notification::TYPE_AFFECTATION.', '.Notification::TYPE_NEW_SIGNALEMENT.')');
    }

    public function down(Schema $schema): void
    {
    }
}
