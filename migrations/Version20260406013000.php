<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260406013000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add author_id to review';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        if (!$sm->tablesExist(['review'])) {
            return;
        }

        $columns = array_map(static fn ($c) => $c->getName(), $sm->listTableColumns('review'));
        if (!in_array('author_id', $columns, true)) {
            $this->addSql('ALTER TABLE review ADD author_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6F675F31B FOREIGN KEY (author_id) REFERENCES `user` (id)');
            $this->addSql('CREATE INDEX IDX_794381C6F675F31B ON review (author_id)');
        }
    }

    public function down(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        if (!$sm->tablesExist(['review'])) {
            return;
        }

        $columns = array_map(static fn ($c) => $c->getName(), $sm->listTableColumns('review'));
        if (in_array('author_id', $columns, true)) {
            $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6F675F31B');
            $this->addSql('DROP INDEX IDX_794381C6F675F31B ON review');
            $this->addSql('ALTER TABLE review DROP author_id');
        }
    }
}
