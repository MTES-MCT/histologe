<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250317105914 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'remove duplicate users emails';
    }

    public function up(Schema $schema): void
    {
        $this->skipIf(
            'histologe' !== getenv('APP'),
            'Cette migration ne s’exécute qu’en environnement de production.'
        );
        // agents to delete
        $this->addSql('DELETE FROM notification WHERE user_id = 18527');
        $agentsToDelete = [18974, 18975, 18976, 18525, 12063, 15309, 15037, 18527, 17591, 19368, 14755, 19528, 25098, 14907, 67589, 12805, 15918];
        $this->addSql('DELETE FROM user_partner WHERE user_id IN ('.implode(',', $agentsToDelete).')');
        $this->addSql('DELETE FROM user WHERE id IN ('.implode(',', $agentsToDelete).')');
        // usagers and archived agents to delete
        $replacements = [
            40446 => 69447,
            55271 => 47392,
            52375 => 69318,
            28521 => 68280,
            15766 => 68641,
            42061 => 29521,
            65076 => 68576,
            68110 => 68474,
            28398 => 28403,
            28857 => 28858,
            49670 => 51920,
            21412 => 69320,
            33176 => 69699,
            11094 => 11308,
            67777 => 68418,
            // archive
            18324 => 18325,
            14507 => 18986,
            5057 => 5061,
            18323 => 18325,
        ];
        foreach ($replacements as $oldId => $newId) {
            $this->addSql('UPDATE signalement_usager SET declarant_id = '.$newId.' WHERE declarant_id = '.$oldId);
            $this->addSql('UPDATE signalement_usager SET occupant_id  = '.$newId.' WHERE occupant_id = '.$oldId);
            $this->addSql('UPDATE suivi SET created_by_id = '.$newId.' WHERE created_by_id = '.$oldId);
            $this->addSql('UPDATE file SET uploaded_by_id = '.$newId.' WHERE uploaded_by_id = '.$oldId);
        }
        $usagersToDelete = [
            40446, 55271, 52375, 28521, 15766, 42061, 65076, 68110, 28398, 28857, 49670, 21412, 33176, 11094, 67777, 18324, 14507, 5057, 18323,
            10355, 25252, 10725, 9107, 11786, 3533, 10782, 17081, 12383, 9548, 13569, 9226, 28287, 65857, 8567, 35788, 9230, 8403, 7312, 29384, 8587, 17232,
        ];
        $this->addSql('DELETE FROM user_partner WHERE user_id IN ('.implode(',', $usagersToDelete).')');
        $this->addSql('DELETE FROM user WHERE id IN ('.implode(',', $usagersToDelete).')');
    }

    public function down(Schema $schema): void
    {
    }
}
