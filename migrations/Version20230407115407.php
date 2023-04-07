<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230407115407 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Affect territoryof partner to all user without territory (except admin and usager)';
    }

    public function up(Schema $schema): void
    {
        $users = $this->connection->fetchAllAssociative('SELECT id, email, partner_id, roles FROM user WHERE territory_id IS NULL AND roles NOT LIKE \'%"ROLE_USAGER"%\' AND roles NOT LIKE  \'%"ROLE_ADMIN"%\'');

        foreach ($users as $user) {
            if (null !== $user['partner_id']) {
                $partner = $this->connection->fetchAssociative('SELECT id, territory_id, nom FROM partner WHERE id = '.$user['partner_id']);
                if ($partner && $partner['territory_id']) {
                    $this->addSql('UPDATE user SET territory_id = '.$partner['territory_id'].' WHERE id ='.$user['id']);
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
