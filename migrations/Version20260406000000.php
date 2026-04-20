<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Tables événements / avis (idempotent si déjà créées à la main ou via schema:update).
 */
final class Version20260406000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create evenement and review tables if missing';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();

        if (!$sm->tablesExist(['evenement'])) {
            $this->addSql('CREATE TABLE evenement (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, max_participants INT NOT NULL, description LONGTEXT DEFAULT NULL, fee NUMERIC(10, 2) NOT NULL, location VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        if (!$sm->tablesExist(['review'])) {
            $this->addSql('CREATE TABLE review (id INT AUTO_INCREMENT NOT NULL, rating INT NOT NULL, comment LONGTEXT DEFAULT NULL, review_date DATE NOT NULL, title VARCHAR(255) DEFAULT NULL, event_id INT NOT NULL, INDEX IDX_794381C671F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C671F7E88B FOREIGN KEY (event_id) REFERENCES evenement (id)');
        }
    }

    public function down(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        if ($sm->tablesExist(['review'])) {
            $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C671F7E88B');
            $this->addSql('DROP TABLE review');
        }
        if ($sm->tablesExist(['evenement'])) {
            $this->addSql('DROP TABLE evenement');
        }
    }
}
