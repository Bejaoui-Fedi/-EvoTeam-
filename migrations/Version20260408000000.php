<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260408000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add seats, contact_phone, note to participation';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        if (!$sm->tablesExist(['participation'])) {
            return;
        }

        $columns = array_map(static fn ($c) => $c->getName(), $sm->listTableColumns('participation'));
        if (!in_array('seats', $columns, true)) {
            $this->addSql("ALTER TABLE participation ADD seats INT NOT NULL DEFAULT 1");
        }
        if (!in_array('contact_phone', $columns, true)) {
            $this->addSql("ALTER TABLE participation ADD contact_phone VARCHAR(255) DEFAULT NULL");
        }
        if (!in_array('note', $columns, true)) {
            $this->addSql("ALTER TABLE participation ADD note LONGTEXT DEFAULT NULL");
        }
    }

    public function down(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        if (!$sm->tablesExist(['participation'])) {
            return;
        }

        $columns = array_map(static fn ($c) => $c->getName(), $sm->listTableColumns('participation'));
        if (in_array('note', $columns, true)) {
            $this->addSql("ALTER TABLE participation DROP note");
        }
        if (in_array('contact_phone', $columns, true)) {
            $this->addSql("ALTER TABLE participation DROP contact_phone");
        }
        if (in_array('seats', $columns, true)) {
            $this->addSql("ALTER TABLE participation DROP seats");
        }
    }
}
