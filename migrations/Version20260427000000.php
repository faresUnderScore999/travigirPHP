<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260427000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create payment_transactions table for Flouci payment audit trail';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE payment_transactions (
            id                  SERIAL PRIMARY KEY,
            reservation_id      INTEGER NOT NULL,
            user_id             INTEGER NOT NULL,
            flouci_payment_id   VARCHAR(200) DEFAULT NULL UNIQUE,
            amount_millimes     INTEGER NOT NULL,
            status              VARCHAR(20) NOT NULL DEFAULT \'INITIATED\',
            ip_address          VARCHAR(45) DEFAULT NULL,
            created_at          TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at          TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        )');

        $this->addSql('CREATE INDEX idx_pt_flouci_id   ON payment_transactions (flouci_payment_id)');
        $this->addSql('CREATE INDEX idx_pt_reservation ON payment_transactions (reservation_id)');
        $this->addSql('CREATE INDEX idx_pt_user        ON payment_transactions (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE payment_transactions');
    }
}
