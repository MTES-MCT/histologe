<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

final class Version20230929142816 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function getDescription(): string
    {
        return 'Replace Esabora by SI-SH in suivis from SI-SH ans created_by_id by user_admin';
    }

    public function up(Schema $schema): void
    {
        $adminEmail = $this->container->getParameter('user_system_email');
        $user = $this->connection->fetchAssociative('SELECT id FROM user WHERE email = \''.$adminEmail.'\'');
        if ($user) {
            $userId = $user['id'];

            // mise à jour description et auteur des suivis en provenance d'Esabora SISH
            $sql = "UPDATE suivi SET description = REPLACE(description, 'Esabora', 'SI-SH'), created_by_id = $userId
                    WHERE description LIKE '%Esabora%'
                    AND is_public = 0
                    AND type = 1
                    AND EXISTS (
                        SELECT 1
                        FROM user u
                        INNER JOIN partner p ON u.partner_id = p.id
                        WHERE u.id = suivi.created_by_id
                        AND p.type = 'ARS'
                    )";

            $this->addSql($sql);

            // mise à jour auteur des suivis en provenance d'esabora SCHS
            $request = 'UPDATE suivi SET created_by_id = \''.$userId.'\' WHERE description LIKE \'%Esabora%\' AND is_public = 0 AND type = 1';
            $this->addSql($request);
        }
    }

    public function down(Schema $schema): void
    {
    }
}
