<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260406030000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create participation and event_history tables';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();

        if (!$sm->tablesExist(['participation'])) {
            $this->addSql('CREATE TABLE participation (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, event_id INT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_AB55E24FA76ED395 (user_id), INDEX IDX_AB55E24F71F7E88B (event_id), UNIQUE INDEX uniq_user_event_participation (user_id, event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24FA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24F71F7E88B FOREIGN KEY (event_id) REFERENCES evenement (id) ON DELETE CASCADE');
        }

        if (!$sm->tablesExist(['event_history'])) {
            $this->addSql('CREATE TABLE event_history (id INT AUTO_INCREMENT NOT NULL, performed_by_id INT DEFAULT NULL, event_id INT DEFAULT NULL, entity_type VARCHAR(30) NOT NULL, action VARCHAR(30) NOT NULL, snapshot_before JSON DEFAULT NULL, snapshot_after JSON DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_D66A5FF2A9B6E2D9 (performed_by_id), INDEX IDX_D66A5FF271F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE event_history ADD CONSTRAINT FK_D66A5FF2A9B6E2D9 FOREIGN KEY (performed_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
            $this->addSql('ALTER TABLE event_history ADD CONSTRAINT FK_D66A5FF271F7E88B FOREIGN KEY (event_id) REFERENCES evenement (id) ON DELETE SET NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();

        if ($sm->tablesExist(['event_history'])) {
            $this->addSql('ALTER TABLE event_history DROP FOREIGN KEY FK_D66A5FF2A9B6E2D9');
            $this->addSql('ALTER TABLE event_history DROP FOREIGN KEY FK_D66A5FF271F7E88B');
            $this->addSql('DROP TABLE event_history');
        }

        if ($sm->tablesExist(['participation'])) {
            $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24FA76ED395');
            $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24F71F7E88B');
            $this->addSql('DROP TABLE participation');
        }
    }
}
