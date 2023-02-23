<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\Qualification;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230221151642 extends AbstractMigration
{
    public const CRITICITE_NDE = [
        'il fait très chaud dans mon logement l’été (ex : baies vitrées avec forte exposition au soleil, absence de stores…) ou le système de chauffage ne fonctionne pas en hiver.',
        "le chauffage est insuffisant j'utilise des appareils d’appoint pour me tenir chaud.",
        "Infiltration ou fuite d'air dans le toit, la charpente, les façades, les murs extérieurs. Parfois sensation de courant d'air.",
        "Infiltration ou fuite d'air dans deux des éléments suivants : toit, charpente, façades, murs extérieurs. Sensation de courant d'air régulièrement ou ponctuellement.",
        'Ma facture est trop élevée et le chauffage est limité dans le logement. ',
        "Le DPE indique une étiquette énergie D ou E. J’ai souvent la sensation d'avoir froid en hiver et/ou très chaud en été.",
        "Le DPE indique une étiquette énergie F ou G ou n’a pas été fait. J’ai tout le temps la sensation d'avoir froid en hiver et/ou très chaud en été.",
        "J’utilise plusieurs chauffages d'appoint et/ou des pièces principales ne possèdent pas de moyen de chauffage.",
        "J’utilise régulièrement un chauffage d'appoint.",
    ];

    public function getDescription(): string
    {
        return 'Update commune, criticite and partner';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commune ADD is_zone_permis_louer TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE criticite ADD qualification JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE partner ADD type VARCHAR(255) DEFAULT NULL, ADD competence JSON DEFAULT NULL');

        foreach (self::CRITICITE_NDE as $criticite) {
            $qualification = [Qualification::NON_DECENCE_ENERGETIQUE];
            $this->addSql('UPDATE criticite SET qualification = \''.json_encode($qualification).'\', modified_at=NOW() WHERE label like "'.$criticite.'"');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commune DROP is_zone_permis_louer');
        $this->addSql('ALTER TABLE criticite DROP qualification');
        $this->addSql('ALTER TABLE partner DROP type, DROP competence');
    }
}
