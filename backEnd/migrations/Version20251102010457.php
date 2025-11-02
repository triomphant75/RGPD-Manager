<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251102010457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'RGPD-compliant user deletion: Add deletion_audit table, anonymization support for treatments, and cascade delete for notifications';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE data_breach_incident (
              id SERIAL NOT NULL,
              created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
              detected_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
              severity VARCHAR(20) NOT NULL,
              description TEXT NOT NULL,
              personal_data_involved BOOLEAN NOT NULL,
              personal_data_types JSON DEFAULT NULL,
              affected_subjects_count INT NOT NULL,
              risk_assessment VARCHAR(20) NOT NULL,
              notification_required BOOLEAN NOT NULL,
              dpo_reviewed BOOLEAN NOT NULL,
              status VARCHAR(20) NOT NULL,
              authority_notified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
              authority_reference VARCHAR(100) DEFAULT NULL,
              subjects_notified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
              containment_actions TEXT DEFAULT NULL,
              remediation_actions TEXT DEFAULT NULL,
              source VARCHAR(255) DEFAULT NULL,
              PRIMARY KEY(id)
            )
        SQL);
        $this->addSql('COMMENT ON COLUMN data_breach_incident.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN data_breach_incident.detected_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN data_breach_incident.authority_notified_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN data_breach_incident.subjects_notified_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql(<<<'SQL'
            CREATE TABLE deletion_audit (
              id SERIAL NOT NULL,
              deleted_by_admin_id INT DEFAULT NULL,
              email_hash VARCHAR(64) NOT NULL,
              user_id_anonymized VARCHAR(50) NOT NULL,
              deletion_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
              deletion_reason TEXT DEFAULT NULL,
              ip_address VARCHAR(45) DEFAULT NULL,
              retention_until TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
              metadata JSON DEFAULT NULL,
              PRIMARY KEY(id)
            )
        SQL);
        $this->addSql('CREATE INDEX IDX_2F0F2EBF54C5E183 ON deletion_audit (deleted_by_admin_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              deletion_audit
            ADD
              CONSTRAINT FK_2F0F2EBF54C5E183 FOREIGN KEY (deleted_by_admin_id) REFERENCES "users" (id) ON DELETE
            SET
              NULL NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql('ALTER TABLE notifications DROP CONSTRAINT FK_6000B0D3A76ED395');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              notifications
            ADD
              CONSTRAINT FK_6000B0D3A76ED395 FOREIGN KEY (user_id) REFERENCES "users" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql('ALTER TABLE treatments DROP CONSTRAINT FK_4A48CE0DB03A8386');
        $this->addSql('ALTER TABLE treatments ADD created_by_anonymized VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE treatments ALTER created_by_id DROP NOT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              treatments
            ADD
              CONSTRAINT FK_4A48CE0DB03A8386 FOREIGN KEY (created_by_id) REFERENCES "users" (id) ON DELETE
            SET
              NULL NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE deletion_audit DROP CONSTRAINT FK_2F0F2EBF54C5E183');
        $this->addSql('DROP TABLE data_breach_incident');
        $this->addSql('DROP TABLE deletion_audit');
        $this->addSql('ALTER TABLE treatments DROP CONSTRAINT fk_4a48ce0db03a8386');
        $this->addSql('ALTER TABLE treatments DROP created_by_anonymized');
        $this->addSql('ALTER TABLE treatments ALTER created_by_id SET NOT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              treatments
            ADD
              CONSTRAINT fk_4a48ce0db03a8386 FOREIGN KEY (created_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql('ALTER TABLE notifications DROP CONSTRAINT fk_6000b0d3a76ed395');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              notifications
            ADD
              CONSTRAINT fk_6000b0d3a76ed395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }
}
