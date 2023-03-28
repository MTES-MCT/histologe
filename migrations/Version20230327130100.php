<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\Qualification;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230327130100 extends AbstractMigration
{
    public const CRITICITE_INSALUBRITE = [
        "il n'y a aucune ventilation dans mon logement.",
        'mon logement situé en sous-sol sans éclairage naturel.',
        'Pièce unique du logement de moins de 9m2',
    ];

    public function getDescription(): string
    {
        return 'Delete score_creation data, add qualifications INSALUBRITE for list of criticités';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP score_creation');

        foreach (self::CRITICITE_INSALUBRITE as $criticite) {
            $qualification = [Qualification::INSALUBRITE];
            $this->addSql('UPDATE criticite SET qualification = \''.json_encode($qualification).'\', modified_at=NOW() WHERE label like "'.$criticite.'"');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD score_creation DOUBLE PRECISION NOT NULL');
    }
}
