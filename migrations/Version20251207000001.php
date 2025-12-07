<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251207000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reset password token fields to user table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD reset_password_token VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD reset_password_token_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP reset_password_token');
        $this->addSql('ALTER TABLE user DROP reset_password_token_expires_at');
    }
}
