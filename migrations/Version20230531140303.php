<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Suivi;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

final class Version20230531140303 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function getDescription(): string
    {
        return 'Set user_system and type auto to first_accepted_affectation suivis';
    }

    public function up(Schema $schema): void
    {
        $adminEmail = $this->container->getParameter('user_system_email');
        $user = $this->connection->fetchAssociative('SELECT id FROM user WHERE email = \''.$adminEmail.'\'');
        if ($user) {
            $request = 'UPDATE suivi SET created_by_id = \''.$user['id'].'\' , type = \''.Suivi::TYPE_AUTO
            .'\' WHERE description LIKE \'%<p>Suite à votre signalement, le ou les partenaires compétents%\'';
            $this->addSql($request);
        }
    }

    public function down(Schema $schema): void
    {
    }
}
