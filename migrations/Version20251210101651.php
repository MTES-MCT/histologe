<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251210101651 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des index pour optimiser la jointure sur les emails (user.email, email_delivery_issue.email)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_edi_email ON email_delivery_issue (email)');
        $this->addSql('CREATE INDEX idx_signalement_mail_occupant ON signalement(mail_occupant)');
        $this->addSql('CREATE INDEX idx_signalement_mail_declarant ON signalement(mail_declarant)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_edi_email ON email_delivery_issue');
        $this->addSql('DROP INDEX idx_signalement_mail_occupant ON signalement');
        $this->addSql('DROP INDEX idx_signalement_mail_declarant ON signalement');
    }
}
