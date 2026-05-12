<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260505023630 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE offers ALTER start_date TYPE DATE');
        $this->addSql('ALTER TABLE offers ALTER end_date TYPE DATE');
        $this->addSql('ALTER TABLE voyages ALTER start_date TYPE DATE');
        $this->addSql('ALTER TABLE voyages ALTER end_date TYPE DATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE offers ALTER start_date TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('ALTER TABLE offers ALTER end_date TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('ALTER TABLE voyages ALTER start_date TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('ALTER TABLE voyages ALTER end_date TYPE TIMESTAMP(0) WITH TIME ZONE');
    }
}
