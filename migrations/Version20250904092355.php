<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250904092355 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'change permissions for API users from user_partner to user_api_permission';
    }

    public function up(Schema $schema): void
    {
        $userPartners = $this->connection->fetchAllAssociative("
            SELECT up.id, up.user_id, up.partner_id
            FROM user_partner up
            INNER JOIN user u ON u.id = up.user_id
            WHERE u.roles LIKE '%ROLE_API_USER%'
        ");

        foreach ($userPartners as $userPartner) {
            $this->addSql('
                INSERT INTO user_api_permission (user_id, partner_id, territory_id, partner_type)
                VALUES (?, ?, NULL, NULL)
            ', [
                $userPartner['user_id'],
                $userPartner['partner_id'],
            ]);
        }

        $this->addSql("
            DELETE up FROM user_partner up
            INNER JOIN user u ON u.id = up.user_id
            WHERE u.roles LIKE '%ROLE_API_USER%'
        ");
    }

    public function down(Schema $schema): void
    {
    }
}
