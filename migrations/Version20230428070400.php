<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230428070400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add codes insee to Metropole de Lyon';
    }

    public function up(Schema $schema): void
    {
        $metropoleLyonCodesInsee = [
            69091, 69096, 69123, 69149, 69199, 69205, 69290, 69259, 69266,
            69381, 69382, 69383, 69384, 69385, 69386, 69387, 69388, 69389,
            69003, 69029, 69033, 69034, 69040, 69044, 69046, 69271, 69063,
            69273, 69068, 69069, 69071, 69072, 69275, 69081, 69276, 69085,
            69087, 69088, 69089, 69100, 69279, 69142, 69250, 69116, 69117,
            69127, 69282, 69283, 69284, 69143, 69152, 69153, 69163, 69286,
            69168, 69191, 69194, 69204, 69207, 69202, 69292, 69293, 69296,
            69244, 69256, 69260, 69233, 69278,
        ];
        $corCodesInsee = [
            69001, 69006, 69008, 69037, 69054, 69060, 69066, 69070, 69075,
            69093, 69102, 69107, 69174, 69130, 69160, 69164, 69169, 69181,
            69183, 69188, 69200, 69214, 69217, 69225, 69229, 69234, 69240,
            69243, 69248, 69254, 69157,
        ];

        $codesInsee = array_merge($metropoleLyonCodesInsee, $corCodesInsee);

        $this->addSql(
            'UPDATE territory SET authorized_codes_insee = \''.json_encode($codesInsee).'\' WHERE zip = \'69\''
        );
    }

    public function down(Schema $schema): void
    {
    }
}
