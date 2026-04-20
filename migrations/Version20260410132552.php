<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260410132552 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event_history DROP FOREIGN KEY FK_D66A5FF2A9B6E2D9');
        $this->addSql('ALTER TABLE event_history DROP FOREIGN KEY FK_D66A5FF271F7E88B');
        $this->addSql('DROP INDEX idx_d66a5ff2a9b6e2d9 ON event_history');
        $this->addSql('CREATE INDEX IDX_A2EDD9E42E65C292 ON event_history (performed_by_id)');
        $this->addSql('DROP INDEX idx_d66a5ff271f7e88b ON event_history');
        $this->addSql('CREATE INDEX IDX_A2EDD9E471F7E88B ON event_history (event_id)');
        $this->addSql('ALTER TABLE event_history ADD CONSTRAINT FK_D66A5FF2A9B6E2D9 FOREIGN KEY (performed_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE event_history ADD CONSTRAINT FK_D66A5FF271F7E88B FOREIGN KEY (event_id) REFERENCES evenement (id) ON DELETE SET NULL');
        $this->addSql('DROP INDEX uniq_user_event_participation ON participation');
        $this->addSql('ALTER TABLE participation ADD payment_method VARCHAR(50) NOT NULL, ADD payment_status VARCHAR(50) NOT NULL, CHANGE seats seats INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event_history DROP FOREIGN KEY FK_A2EDD9E42E65C292');
        $this->addSql('ALTER TABLE event_history DROP FOREIGN KEY FK_A2EDD9E471F7E88B');
        $this->addSql('DROP INDEX idx_a2edd9e42e65c292 ON event_history');
        $this->addSql('CREATE INDEX IDX_D66A5FF2A9B6E2D9 ON event_history (performed_by_id)');
        $this->addSql('DROP INDEX idx_a2edd9e471f7e88b ON event_history');
        $this->addSql('CREATE INDEX IDX_D66A5FF271F7E88B ON event_history (event_id)');
        $this->addSql('ALTER TABLE event_history ADD CONSTRAINT FK_A2EDD9E42E65C292 FOREIGN KEY (performed_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE event_history ADD CONSTRAINT FK_A2EDD9E471F7E88B FOREIGN KEY (event_id) REFERENCES evenement (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE participation DROP payment_method, DROP payment_status, CHANGE seats seats INT DEFAULT 1 NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_event_participation ON participation (user_id, event_id)');
    }
}
