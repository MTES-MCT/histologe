<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\InterventionStatus;
use App\Entity\Enum\InterventionType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230407084228 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'migrate date_visite in intervention table and remove from signalement table';
    }

    public function preUp(Schema $schema): void
    {
        parent::preUp($schema);

        $sql = 'SELECT id, date_visite, is_occupant_present_visite FROM signalement';
        $query = $this->connection->prepare($sql);
        $listeSignalements = $query->executeQuery()->fetchAllAssociative();

        foreach ($listeSignalements as $rowSignalement) {
            $idSignalement = $rowSignalement['id'];
            $dateVisite = $rowSignalement['date_visite'];
            $isOccupantPresentVisite = $rowSignalement['is_occupant_present_visite'];

            if ($dateVisite) {
                $this->connection->insert(
                    'intervention',
                    [
                        'signalement_id' => $idSignalement,
                        'date' => $dateVisite,
                        'occupant_present' => $isOccupantPresentVisite,
                        'type' => InterventionType::VISITE->name,
                        'status' => InterventionStatus::PLANNED->name,
                        'documents' => '[]',
                    ]
                );
            }
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP date_visite, DROP is_occupant_present_visite');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD date_visite DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_occupant_present_visite TINYINT(1) DEFAULT NULL');
    }
}
