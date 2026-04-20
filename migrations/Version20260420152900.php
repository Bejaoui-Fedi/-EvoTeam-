<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Synchronise la base de données avec les entités Symfony actuelles.
 *
 * État réel de la DB (après tentatives partielles précédentes) :
 * - exercise_completion: index déjà OK (IDX_78A7EB1E*), mais FK MANQUANTES
 * - objective: index déjà OK (IDX_B996F101A76ED395), mais FK vers user MANQUANTE
 * - exercise/user/user_profile/messenger_messages: colonnes à vérifier/synchroniser
 */
final class Version20260420152900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Restore missing FK constraints and sync column definitions';
    }

    public function up(Schema $schema): void
    {
        // =================================================================
        // 1. exercise_completion : restaurer les FK manquantes
        //    (les index IDX_78A7EB1E* existent déjà)
        // =================================================================
        $this->addSql('ALTER TABLE exercise_completion ADD CONSTRAINT FK_78A7EB1EA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE exercise_completion ADD CONSTRAINT FK_78A7EB1EE934951A FOREIGN KEY (exercise_id) REFERENCES exercise (id)');
        $this->addSql('ALTER TABLE exercise_completion ADD CONSTRAINT FK_78A7EB1E73484933 FOREIGN KEY (objective_id) REFERENCES objective (id)');

        // =================================================================
        // 2. objective : ajouter la FK manquante vers user
        //    (l'index IDX_B996F101A76ED395 existe déjà)
        // =================================================================
        $this->addSql('ALTER TABLE objective ADD CONSTRAINT FK_B996F101A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');

        // =================================================================
        // 3. objective : corriger les colonnes
        // =================================================================
        $this->addSql('ALTER TABLE objective
            CHANGE title title VARCHAR(255) NOT NULL,
            CHANGE updated_at updated_at DATETIME NOT NULL,
            CHANGE level level VARCHAR(50) NOT NULL,
            CHANGE is_published is_published TINYINT(1) NOT NULL,
            CHANGE user_id user_id INT NOT NULL
        ');

        // =================================================================
        // 4. exercise : synchroniser les colonnes nullable
        // =================================================================
        $this->addSql('ALTER TABLE exercise
            CHANGE type type VARCHAR(100) DEFAULT NULL,
            CHANGE media_url media_url VARCHAR(255) DEFAULT NULL,
            CHANGE calendar_event_id calendar_event_id VARCHAR(255) DEFAULT NULL
        ');

        // =================================================================
        // 5. user : synchroniser les colonnes nullable + defaults
        // =================================================================
        $this->addSql("ALTER TABLE user
            CHANGE telephone telephone VARCHAR(255) DEFAULT NULL,
            CHANGE reset_token reset_token VARCHAR(255) DEFAULT NULL,
            CHANGE token_expiry token_expiry DATETIME DEFAULT NULL,
            CHANGE level level VARCHAR(50) DEFAULT 'DEBUTANT' NOT NULL,
            CHANGE last_activity_date last_activity_date DATE DEFAULT NULL
        ");

        // =================================================================
        // 6. user_profile : synchroniser les colonnes nullable
        // =================================================================
        $this->addSql('ALTER TABLE user_profile
            CHANGE avatar avatar VARCHAR(255) DEFAULT NULL,
            CHANGE date_naissance date_naissance DATE DEFAULT NULL,
            CHANGE langue langue VARCHAR(100) DEFAULT NULL,
            CHANGE date_modification date_modification DATETIME DEFAULT NULL
        ');

        // =================================================================
        // 7. messenger_messages : delivered_at nullable
        // =================================================================
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Supprimer les FK restaurées
        $this->addSql('ALTER TABLE exercise_completion DROP FOREIGN KEY FK_78A7EB1EA76ED395');
        $this->addSql('ALTER TABLE exercise_completion DROP FOREIGN KEY FK_78A7EB1EE934951A');
        $this->addSql('ALTER TABLE exercise_completion DROP FOREIGN KEY FK_78A7EB1E73484933');
        $this->addSql('ALTER TABLE objective DROP FOREIGN KEY FK_B996F101A76ED395');

        // Revert columns (best effort)
        $this->addSql('ALTER TABLE exercise
            CHANGE type type VARCHAR(100) NOT NULL,
            CHANGE media_url media_url VARCHAR(255) NOT NULL,
            CHANGE calendar_event_id calendar_event_id VARCHAR(255) NOT NULL
        ');
        $this->addSql("ALTER TABLE user
            CHANGE telephone telephone VARCHAR(255) NOT NULL,
            CHANGE reset_token reset_token VARCHAR(255) NOT NULL,
            CHANGE token_expiry token_expiry DATETIME NOT NULL,
            CHANGE level level VARCHAR(50) NOT NULL,
            CHANGE last_activity_date last_activity_date DATE NOT NULL
        ");
        $this->addSql('ALTER TABLE user_profile
            CHANGE avatar avatar VARCHAR(255) NOT NULL,
            CHANGE date_naissance date_naissance DATE NOT NULL,
            CHANGE langue langue VARCHAR(100) NOT NULL,
            CHANGE date_modification date_modification DATETIME NOT NULL
        ');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME NOT NULL');
    }
}
