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
        /*$this->skipIf(
            'histologe' !== getenv('APP'),
            'Cette migration ne s’exécute qu’en environnement de production.'
        );*/
        // agents to delete
        $agentsToDelete = [18974, 18975, 18976, 18525, 12063, 15309, 15037, 18527, 17591, 19368, 14755, 19528, 25098, 14907, 67589, 12805, 15918];
        $this->addSql('DELETE FROM notification WHERE user_id IN ('.implode(',', $agentsToDelete).')');
        $this->addSql('DELETE FROM user_partner WHERE user_id IN ('.implode(',', $agentsToDelete).')');
        $this->addSql('DELETE FROM user WHERE id IN ('.implode(',', $agentsToDelete).')');
        // usagers and archived agents to delete
        $replacements = [
            69447 => 40446,
            55271 => 47392,
            69318 => 52375,
            68280 => 28521,
            68641 => 15766,
            42061 => 29521,
            68576 => 65076,
            68110 => 68474,
            28398 => 28403,
            28857 => 28858,
            51920 => 49670,
            69320 => 21412,
            69699 => 33176,
            11094 => 11308,
            68418 => 67777,
            71060 => 30663,
            71278 => 40573,
            69771 => 70685,
            71501 => 31611,
            70396 => 69595,
            71730 => 64480,
            71389 => 20389,
            13569 => 13568,
            72270 => 72271,
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
            $this->addSql('UPDATE notification SET user_id = '.$newId.' WHERE user_id = '.$oldId);
            $this->addSql('UPDATE affectation SET answered_by_id = '.$newId.' WHERE answered_by_id = '.$oldId);
        }
        $usagersToDelete = [
            69447, 55271, 69318, 68280, 68641, 42061, 68576, 68110, 28398, 28857, 51920, 69320, 69699, 11094, 68418,
            71060, 71278, 69771, 71501, 70396, 71730, 71389, 13569, 72270,
            18324, 14507, 5057, 18323,
            10355, 25252, 10725, 9107, 11786, 3533, 10782, 17081, 12383, 9548, 9226, 28287, 65857, 8567, 35788, 9230, 8403, 7312, 29384, 8587, 17232,
        ];
        $this->addSql('DELETE FROM history_entry WHERE user_id IN ('.implode(',', $usagersToDelete).')');
        $this->addSql('DELETE FROM user_partner WHERE user_id IN ('.implode(',', $usagersToDelete).')');
        $this->addSql('DELETE FROM user WHERE id IN ('.implode(',', $usagersToDelete).')');
    }

    public function down(Schema $schema): void
    {
    }
}
