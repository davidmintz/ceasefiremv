<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Hand-written (pdo_sqlite wasn't available to run migrations:diff). The
 * DC2Type comments are how DBAL 3 tags immutable datetime columns; without
 * them doctrine:schema:validate reports the schema out of sync.
 */
final class Version20261106000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create rsvp table for double opt-in meeting RSVPs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE rsvp (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            meeting VARCHAR(32) NOT NULL,
            email VARCHAR(180) NOT NULL,
            name VARCHAR(60) NOT NULL,
            mode VARCHAR(16) NOT NULL,
            token_hash VARCHAR(64) DEFAULT NULL,
            token_expires_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
            ,
            created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
            ,
            updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
            ,
            verified_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_rsvp_meeting_email ON rsvp (meeting, email)');
        $this->addSql('CREATE UNIQUE INDEX uniq_rsvp_token_hash ON rsvp (token_hash)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE rsvp');
    }
}
